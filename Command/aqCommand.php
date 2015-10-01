<?php

namespace eDemy\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class aqCommand extends ContainerAwareCommand
{
    private $client;
    private $domain;
    private $categoria;
    private $subcategoria;
    private $products;
    private $writeSubcategories;
    private $writeAllSubcategories;
    private $getMaxCategories;
    private $maxSubcategories;
    private $firstSubcategory;
    private $subcategory_count;
    private $getProducts;
    private $output, $input;
    private $getCategoryProducts;
    private $prices;
    private $count, $subcount;
    
    protected function configure()
    {
        $this
            ->setName('get:aq')
            ->setDescription('Show HTML Response')
            ->addArgument('host', InputArgument::REQUIRED, 'What URL do you want to get?')
            ->addArgument('doc_host', InputArgument::REQUIRED, 'What URL do you want to get?')
            ->addOption('getCategories', null, InputOption::VALUE_OPTIONAL, 'Get Categories')
            ->addOption('getMaxCategories', null, InputOption::VALUE_OPTIONAL, 'Number of categories to crawl')
            ->addOption('getSubcategories', null, InputOption::VALUE_OPTIONAL, 'Get subcategories?')
            ->addOption('firstSubcategory', null, InputOption::VALUE_OPTIONAL, 'Number of subcategories to crawl')
            ->addOption('maxSubcategories', null, InputOption::VALUE_OPTIONAL, 'Number of subcategories to crawl')
            ->addOption('getProducts', null, InputOption::VALUE_OPTIONAL, 'Get products?')
            ->addOption('writeAllSubcategories', null, InputOption::VALUE_OPTIONAL, 'Write all subcategories file?')
            ->addOption('writeSubcategories', null, InputOption::VALUE_OPTIONAL, 'Write subcategories files?')
            ->addOption('ignoreSubcategories', null, InputOption::VALUE_OPTIONAL, 'Ignore subcategories')

            ->addOption('last', null, InputOption::VALUE_OPTIONAL, 'Remove last characters from category?')
            ->addOption('handle', null, InputOption::VALUE_OPTIONAL, 'Append subcategory to handle?')
            ->addOption('categoryProducts', null, InputOption::VALUE_OPTIONAL, 'Get products from category?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->count = 0;
        $this->subcount = 0;
        $this->host = $this->input->getArgument('host');
        $this->doc_host = $this->input->getArgument('doc_host');
        $this->domain = $this->host; // . 'es/productos.html';
        $this->products = array();
        $this->client = new Client();
        $crawler = $this->follow($this->domain . '/aquaticpsh');
        //$this->loadPrices();

        $this->getMaxCategories = $this->input->getOption('getMaxCategories');
        $this->getCategories = $this->input->getOption('getCategories');
        $this->getSubcategories = $this->input->getOption('getSubcategories');
        $this->maxSubcategories = null;
        $this->maxSubcategories = $this->input->getOption('maxSubcategories');
        $this->firstSubcategory = 1;
        $this->firstSubcategory = $this->input->getOption('firstSubcategory');
        $this->firstSubcategory--;
        $this->subcategory_count = 0;
        $this->getProducts = $this->input->getOption('getProducts');
        $this->writeAllSubcategories = false;
        $this->writeAllSubcategories = $this->input->getOption('writeAllSubcategories');
        $this->writeSubcategories = false;
        $this->writeSubcategories = $this->input->getOption('writeSubcategories');
        $this->ignoreSubcategories = explode(',', $this->input->getOption('ignoreSubcategories'));
        $igno = array();
        if(count($this->ignoreSubcategories)) {
            foreach($this->ignoreSubcategories as $ignoreSubcategory) {
                $igno[] = (int) $ignoreSubcategory;
            }
            $this->ignoreSubcategories = $igno;
        }
        if($this->getCategories) {
            $this->getCategoryProducts = $this->input->getOption('categoryProducts');
            $this->categorias($crawler, '.tree > li');
        }
    }

    protected function categorias($crawler, $xpath) {
        $c_cats = $crawler->filter($xpath);
        if (iterator_count($c_cats)) {
            foreach ($c_cats as $i => $content) {
                $c_cat = new Crawler($content);
                $this->categoria = $c_cat->filter('a')->text();
                $this->output->writeln('<info>' . 'CAT: ' . $this->categoria . '</info>');

                $c_subcats = $c_cat->filter('ul')->first()->children();
                if (iterator_count($c_subcats)) {
                    foreach ($c_subcats as $i => $content) {
                        $c_subcat = new Crawler($content);
                        $this->subcategoria = $c_subcat->filter('a')->text();
                        $this->output->writeln('<info>'.'SUBCAT: '.$this->subcategoria.'</info>');

                        if (iterator_count($c_subcat->filter('ul')->first())) {
                            $c_subsubcats = $c_subcat->filter('ul')->first()->children();
                            if (iterator_count($c_subsubcats)) {
                                foreach ($c_subsubcats as $i => $content) {
                                    $c_subsubcat = new Crawler($content);
                                    $this->subsubcategoria = $c_subsubcat->filter('a')->text();
                                    $this->output->writeln('<info>'.'SUBSUBCAT: '.$this->subsubcategoria.'</info>');

                                    //follow
                                    $link = $c_subsubcat->filter('a')->attr('href');
                                    try {
                                        $c_products = $this->follow($link);
                                    } catch(\Exception $e) {

                                    }

                                    //while next get the products
                                    $continue = false;
                                    do {
                                        if (iterator_count($c_products->filter('#product_list'))) {
                                            $c_products_list = $c_products->filter('#product_list')->children();
                                            if (iterator_count($c_products_list)) {
                                                foreach ($c_products_list as $i => $content) {
                                                    $c_product = new Crawler($content);
                                                    $link = $c_product->filter('h3 a')->attr('href');
                                                    //die(var_dump($link));
                                                    try {
                                                        $c_product = $this->follow($link);
                                                        $this->product($c_product);
                                                    } catch (\Exception $e) {

                                                    }
                                                }
                                            }
                                        }
                                        //die(var_dump($c_products->filter('#pagination_next a')->count()));
                                        if ($c_products->filter('#pagination_next a')->count()) {
                                            $next = $c_products->filter('#pagination_next a')->first()->attr('href');
                                            //die(var_dump($this->domain));
                                            $c_products = $this->follow($this->domain.$next);
                                            $continue = true;
                                        } else {
                                            $continue = false;
                                        }

                                    } while ($continue);
                                }
                            }
                        }
                    }
                }
            }
            if ($this->writeAllSubcategories) {
                $this->writeCsv($this->products, 'allsubcategories');
                empty($this->products);
            }
        } else {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $this->output->writeln("No hay categor�as.");
            }
        }
    }


    protected function product($c_product) {
        $title = $c_product->filter('#primary_block h1')->text();
        $this->output->writeln('<question>'. $this->subcount++ .' NAME: '.$title.'</question>');

        $description = $c_product->filter('#short_description_content')->text();
        $this->output->writeln('DESC: '.$description);

        $price = $c_product->filter('#our_price_display')->text();
        $this->output->writeln('PRICE: '.$price);

        $img = null;
        if($c_product->filter('#thumbs_list ul li a')->count()) {
            $c_images = $c_product->filter('#thumbs_list ul li a');
            foreach ($c_images as $i => $content) {
                $c_image = new Crawler($content);
                $img = $c_image->attr('href');
                $this->output->writeln('IMG: ' . $img);
            }
        }
        /*
        $this->count++;
        $this->subcount++;
        $pvp = $this->getPvp($number);
        $this->products[$this->count]['Handle'] = $this->sluggify($title);
        $this->products[$this->count]['Title'] = $title;
        $this->products[$this->count]['Body'] = $body;
        $this->products[$this->count]['Vendor'] = 'sera';
        $this->products[$this->count]['Type'] = $this->categoria . ' - ' . $this->subcategoria;
        $this->products[$this->count]['Tags'] = $this->categoria . ' - ' . $this->subcategoria . ",";
        if($pvp) {
            $this->products[$this->count]['Published'] = 'TRUE';
        } else {
            $this->products[$this->count]['Published'] = 'FALSE';
        }
        $this->products[$this->count]['Option1 Name'] = 'Tama�o';
        $this->products[$this->count]['Option1 Value'] = trim($size . ' ' . $comment);
        $this->products[$this->count]['Option2 Name'] = null;
        $this->products[$this->count]['Option2 Value'] = null;
        $this->products[$this->count]['Option3 Name'] = null;
        $this->products[$this->count]['Option3 Value'] = null;
        $this->products[$this->count]['Variant SKU'] = $number;
        $this->products[$this->count]['Variant Grams'] = $this->getPeso($number, $size, 'peso');
        $this->products[$this->count]['Variant Inventory Tracker'] = null; //'shopify';
        $this->products[$this->count]['Variant Inventory Qty'] = 100;
        $this->products[$this->count]['Variant Inventory Policy'] = 'continue';
        $this->products[$this->count]['Variant Fulfillment Service'] = 'manual';
        if(is_numeric($number)) {
            $this->products[$this->count]['Variant Price'] = $pvp;
        } else {
            $this->products[$this->count]['Variant Price'] = null;
        }
        $this->products[$this->count]['Variant Compare At Price'] = null;
        $this->products[$this->count]['Variant Requires Shipping'] = 'TRUE';
        $this->products[$this->count]['Variant Taxable'] = 'TRUE';
        //$this->products[$this->count]['Variant Barcode'] = null;
        if($img) {
            $img_local = explode('/', $img);
            $img_local = end($img_local);
            $img_edemy = "http://edemy.es/d/sera/resized/" . $img_local;
            //die(var_dump($img_local));
            die(var_dump($img_local));

            file_put_contents("/var/www/" . $this->doc_host . "/www/web/d/sera/original/" . $img_local, file_get_contents($img));
            $this->products[$this->count]['Image Src'] = $img_edemy;
            $this->products[$this->count]['Image Alt Text'] = $title . ' ' . $size;
            $this->products[$this->count]['Variant Image'] = $img_edemy;
        } else {
            $this->products[$this->count]['Image Src'] = null;
            $this->products[$this->count]['Image Alt Text'] = null;
            $this->products[$this->count]['Variant Image'] = null;
        }
        $this->products[$this->count]['Variant Weight Unit'] = $this->getPeso($number, $size, 'unidad');



        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln('<comment>' . $title . '</comment>' . ' ' . $size . ' ' . $img);
        }
        //$this->output->writeln($subtitle);
        //$this->output->writeln($bodytext);
        //$this->output->writeln($size . "  " . $img);
        //if($description) $this->output->writeln($description);
        //if($feedingnote) $this->output->writeln($feedingnote);
        //if($composition) $this->output->writeln($composition);
        //if($qualityanalysis) $this->output->writeln($qualityanalysis);
        //if($additives) $this->output->writeln($additives);
        */
    }

    protected function follow($link) {
        return new Crawler($this->getContents($this->client->get($link)->getBody()));
    }
    
    protected function trim($regex, $cad, $rtrim = 0) {
        $cad = trim(preg_replace($regex, '', $cad));
        if($rtrim) {
            return substr_replace($cad, "", $rtrim);
        } else {
            return $cad;
        }
    }
    
    protected function writeCsv($products, $file) {
        die('a');
        $fp = fopen("/var/www/" . $this->doc_host . "/www/web/d/sera/csv/" . $file . '.csv', 'w');
        fputcsv($fp, array(
            'Handle', 
            'Title',
            'Body (HTML)',
            'Vendor',
            'Type',
            'Tags',
            'Published',
            'Option1 Name',
            'Option1 Value',
            'Option2 Name',
            'Option2 Value',
            'Option3 Name',
            'Option3 Value',
            'Variant SKU',
            'Variant Grams',
            'Variant Inventory Tracker',
            'Variant Inventory Qty',
            'Variant Inventory Policy',
            'Variant Fulfillment Service',
            'Variant Price',
            'Variant Compare At Price',
            'Variant Requires Shipping',
            'Variant Taxable',
            //'Variant Barcode',
            'Image Src',
            'Image Alt Text',
            //'Gift Card',
            //'SEO Title',
            //'SEO Description',
            //'Google Shopping / Google Product Category',
            //'Google Shopping / Gender',
            //'Google Shopping / Age Group',
            //'Google Shopping / MPN',
            //'Google Shopping / AdWords Grouping',
            //'Google Shopping / AdWords Labels',
            //'Google Shopping / Condition',
            //'Google Shopping / Custom Product',
            //'Google Shopping / Custom Label 0',
            //'Google Shopping / Custom Label 1',
            //'Google Shopping / Custom Label 2',
            //'Google Shopping / Custom Label 3',
            //'Google Shopping / Custom Label 4',
            'Variant Image',
            'Variant Weight Unit'
        ),',');
        $i = 1;
        foreach ($products as $product) {
            if($product['Variant Price'] != null) {
                if($i == 1) {
                    $product_prev = $product;
                    if(($product['Variant Price'] != null) and ($product['Title'] != null)) {
                        fputcsv($fp, array(
                            $product['Handle'], //handle
                            $product['Title'], //Title
                            $product['Body'], //Body (HTML)
                            $product['Vendor'], //Vendor
                            $product['Type'], //Type
                            $product['Tags'], //Tags
                            $product['Published'], //Published
                            $product['Option1 Name'], //Option1 Name
                            $product['Option1 Value'], //Option1 Value
                            $product['Option2 Name'], //Option1 Name
                            $product['Option2 Value'], //Option1 Value
                            $product['Option3 Name'], //Option1 Name
                            $product['Option3 Value'], //Option1 Value
                            $product['Variant SKU'], //Variant SKU
                            $product['Variant Grams'], //Variant Grams
                            $product['Variant Inventory Tracker'], //Variant Inventory Tracker
                            $product['Variant Inventory Qty'], //Variant Inventory Qty
                            $product['Variant Inventory Policy'], //Variant Inventory Policy
                            $product['Variant Fulfillment Service'], //Variant Fulfillment Service
                            $product['Variant Price'], //Variant Price
                            $product['Variant Compare At Price'], //Variant Compare at Price
                            $product['Variant Requires Shipping'], //Variant Requires Shipping
                            $product['Variant Taxable'], //Variant Taxable
                            //$product['Variant Barcode'], //Variant Barcode
                            $product['Image Src'], //Image Src
                            $product['Image Alt Text'], //Image Alt Text
                            $product['Variant Image'], //Image Src
                            $product['Variant Weight Unit'], //Image Src
                            
                            //$product[' Alt Text'], //Image Alt Text
                            //str_replace(';', '', $linea['direccion']),
                            //str_replace('"', '', (str_replace('=', '', $linea['cp']))),
                        ),',');
                    }
                } else {
                    
                    //$this->output->writeln($products[$i]['Title']);
                    //$this->output->writeln($products[$i - 1]['Title']);
                    //die();
                    if(($product['Variant Price'] != null) and ($product['Title'] != null)) {

                        if ($product['Title'] == $product_prev['Title']) {
                            fputcsv($fp, array(
                                $product['Handle'], //handle
                                null, //Title
                                null, //Body (HTML)
                                null, //Vendor
                                null, //Type
                                null, //Tags
                                null, //Published
                                null, //Option1 Name
                                $product['Option1 Value'], //Option1 Value
                                null, //Option2 Name
                                null, //Option2 Value
                                null, //Option3 Name
                                null, //Option3 Value
                                $product['Variant SKU'], //Variant SKU
                                $product['Variant Grams'], //Variant Grams
                                $product['Variant Inventory Tracker'], //Variant Inventory Tracker
                                $product['Variant Inventory Qty'], //Variant Inventory Qty
                                $product['Variant Inventory Policy'], //Variant Inventory Policy
                                $product['Variant Fulfillment Service'], //Variant Fulfillment Service
                                $product['Variant Price'], //Variant Price
                                $product['Variant Compare At Price'], //Variant Compare at Price
                                $product['Variant Requires Shipping'], //Variant Requires Shipping
                                $product['Variant Taxable'], //Variant Taxable
                                //$product['Variant Barcode'], //Variant Barcode
                                $product['Image Src'], //Image Src
                                $product['Image Alt Text'], //Image Alt Text
                                $product['Variant Image'], //Image Src
                                $product['Variant Weight Unit'], //Image Src
                                //$product[' Alt Text'], //Image Alt Text
                                //str_replace(';', '', $linea['direccion']),
                                //str_replace('"', '', (str_replace('=', '', $linea['cp']))),
                            ),',');
                        } else {
                            fputcsv($fp, array(
                                $product['Handle'], //handle
                                $product['Title'], //Title
                                $product['Body'], //Body (HTML)
                                $product['Vendor'], //Vendor
                                $product['Type'], //Type
                                $product['Tags'], //Tags
                                $product['Published'], //Published
                                $product['Option1 Name'], //Option1 Name
                                $product['Option1 Value'], //Option1 Value
                                $product['Option2 Name'], //Option1 Name
                                $product['Option2 Value'], //Option1 Value
                                $product['Option3 Name'], //Option1 Name
                                $product['Option3 Value'], //Option1 Value
                                $product['Variant SKU'], //Variant SKU
                                $product['Variant Grams'], //Variant Grams
                                $product['Variant Inventory Tracker'], //Variant Inventory Tracker
                                $product['Variant Inventory Qty'], //Variant Inventory Qty
                                $product['Variant Inventory Policy'], //Variant Inventory Policy
                                $product['Variant Fulfillment Service'], //Variant Fulfillment Service
                                $product['Variant Price'], //Variant Price
                                $product['Variant Compare At Price'], //Variant Compare at Price
                                $product['Variant Requires Shipping'], //Variant Requires Shipping
                                $product['Variant Taxable'], //Variant Taxable
                                //$product['Variant Barcode'], //Variant Barcode
                                $product['Image Src'], //Image Src
                                $product['Image Alt Text'], //Image Alt Text
                                $product['Variant Image'], //Image Src
                                $product['Variant Weight Unit'], //Image Src
                                //$product[' Alt Text'], //Image Alt Text
                                //str_replace(';', '', $linea['direccion']),
                                //str_replace('"', '', (str_replace('=', '', $linea['cp']))),
                            ),',');
                        }
                    }
                    $product_prev = $product;
                }
                $i++;
            }
        }
        fclose($fp);
    }
    protected function getContents($stream) {
        ob_start();
        echo $stream;
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    private function sluggify($str){
        # special accents
        $a = array('�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','�','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','?','?','J','j','K','k','L','l','L','l','L','l','?','?','L','l','N','n','N','n','N','n','?','O','o','O','o','O','o','�','�','R','r','R','r','R','r','S','s','S','s','S','s','�','�','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','�','Z','z','Z','z','�','�','?','�','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','?','?','?','?','?','?');
        $b = array('A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','s','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','o','u','u','u','u','y','y','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','D','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','IJ','ij','J','j','K','k','L','l','L','l','L','l','L','l','l','l','N','n','N','n','N','n','n','O','o','O','o','O','o','OE','oe','R','r','R','r','R','r','S','s','S','s','S','s','S','s','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Y','Z','z','Z','z','Z','z','s','f','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','A','a','AE','ae','O','o');
        return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/','/[ -]+/','/^-|-$/'),array('','-',''),str_replace($a,$b,$str)));
    }

    private function loadPrices() {
        if (($handle = fopen("/var/www/" . $this->doc_host . "/www/web/d/sera/csv/precios_sera.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                if(is_numeric($data[1])) {
                    $ref = (int) $data [1];
                    $coste = (float) $data[4];
                    $pvp = (float) $data[5];
                    if($pvp == 0) $pvp = $coste * 1.5;
                    $pesos = explode(' ', trim($data[2]));
                    $peso = (float) $pesos[0];
                    $unidad = $pesos[1];

                    $this->prices[$ref]['coste'] = $coste;
                    $this->prices[$ref]['pvp'] = $pvp;
                    $this->prices[$ref]['peso'] = $peso;
                    $this->prices[$ref]['unidad'] = $unidad;
                    //$this->output->writeln($data[1]);
                }
            }
            fclose($handle);
        }
    }
    
    private function getPvp($ref) {
        if(array_key_exists($ref, $this->prices)) {
            setlocale(LC_MONETARY, 'es_ES');
            return (float) money_format('%i', $this->prices[$ref]['pvp']);
        } else {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $this->output->writeln("precio no encontrado en ref: " . $ref);
            }
            return null;
        }
    }

    private function getCoste($ref) {
        if(array_key_exists($ref, $this->prices)) {
            return (float) $this->prices[$ref]['coste'];
        } else {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $this->output->writeln("precio no encontrado en ref: " . $ref);
            }
            return null;
        }
    }

    private function getPeso($ref, $size, $info = 'peso') {
        preg_match_all('/\((.*?)\)/', $size, $matches);
        if(count($matches)>1) {
            if($matches[1]) {
                if(count($matches[1])) {
                    $pesos = explode(' ', trim($matches[1][0]));
                }
            } else {
                $pesos = explode(' ', trim($size));
            }
            if(count($pesos) > 1) {
                $peso = (float) str_replace(',', '.', $pesos[0]);
                $unidad = $pesos[1];
            } else {
                $unidad = 'no unit';
            }
        }
        if ($unidad == 'ml') {
            $peso = $peso * 0.6;
        } elseif($unidad == 'kg') {
            $peso = $peso * 1000;
            $unidad = 'g';
        } elseif($unidad == 'l') {
            $peso = $peso * 600;
            $unidad = 'g';
        } elseif($unidad == 'g') {
            
        } else {
            $peso = 500;
            $unidad = 'g';
        }
        if($info == 'peso') {
            return $peso;
        } 
        if($info == 'unidad') {
            return $unidad;
        }
    }
/*
    private function getUnidad($ref, $size) {
        if(array_key_exists($ref, $this->prices)) {
            if($this->prices[$ref]['unidad'] == 'ml') {
                return 'g';
            } else {
                return $this->prices[$ref]['unidad'];
            }
        } else {
            $this->output->writeln("peso no encontrado en ref: " . $ref);
            return null;
        }
    }
    */
}
