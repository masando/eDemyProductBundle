<?php

namespace eDemy\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class prCommand extends ContainerAwareCommand
{
    private $client;
    private $domain;
    private $categoria;
    private $subcategoria;
    private $products;
    private $category_num;
    private $getProducts;
    private $output, $input;
    
    protected function configure()
    {
        $this
            ->setName('get:pr')
            ->setDescription('Show HTML Response')
            ->addArgument('url', InputArgument::OPTIONAL, 'What URL do you want to get?')
            ->addOption('cat', null, InputOption::VALUE_OPTIONAL, 'Category number')
            ->addOption('products', null, InputOption::VALUE_OPTIONAL, 'Get products?')
            ->addOption('last', null, InputOption::VALUE_OPTIONAL, 'Remove last characters from category?')
            ->addOption('handle', null, InputOption::VALUE_OPTIONAL, 'Append subcategory to handle?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->count = 0;
        $this->domain = 'http://www.__.com/comercio/index.php';
        $this->domain2 = 'http://www.__.es/d';
        $this->products = array();
        $this->client = new Client();
        $crawler = $this->follow($this->domain);
        $this->output = $output;
        $this->input = $input;
        if($this->input->getOption('cat') != null) {
            $this->category_num = $this->input->getOption('cat');
        } else {
            $this->category_num = null;
        }
        $this->getProducts = $this->input->getOption('products');
        $this->categorias($crawler, '.menudiv > ul > li');
    }

    protected function categorias($crawler, $xpath) {
        $c = $crawler;
        if($this->category_num != null) {
            $filter = $crawler->filter($xpath)->eq($this->category_num);
        } else {
            $filter = $crawler->filter($xpath);
        }
        if (iterator_count($filter)) {
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($this->input->getOption('last') == true) {
                    $this->categoria = $this->trim('/\((\d+)\)/', $crawler->filter('a')->text(), 0);
                } else {
                    $this->categoria = $this->trim('/\((\d+)\)/', $crawler->filter('a')->text(), 0);
                }
                
                $this->output->writeln('<info>' . 'CAT: ' . $this->categoria . '</info>');
                //$crawler = $this->follow($crawler->filter('a')->attr('href'));
                $this->subcategorias($crawler, 'ul > li');
                //$this->writeCsv($this->products, $this->categoria);
                empty($this->products);
            }
        } else {
            $this->output->writeln("No hay categorías.");
        }
        die();
    }

    protected function subcategorias($crawler, $xpath) {
        $filter = $crawler->filter($xpath);
        if (iterator_count($filter)) {
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($this->input->getOption('last') == true) {
                    $this->subcategoria = $this->trim('/\((\d+)\)/', $crawler->filter('a')->text(), 0);
                } else {
                    $this->subcategoria = $this->trim('/\((\d+)\)/', $crawler->filter('a')->text(), 0);
                }                
                
                $this->output->writeln('<info>' . 'SUBCAT: ' . $this->subcategoria . '</info>');
                if($this->getProducts) {
                    //$crawler = $this->follow($crawler->filter('a')->attr('href'));
                    //$this->products($crawler, 'table.box_width_cont center');
                    //$next = $crawler->filter('a.pageResults[title=" Siguiente "]');
                    //die(var_dump(iterator_count($next)));
                    while(iterator_count($next) == 2) {
                        //$crawler = $this->follow($next->attr('href'));
                        //$this->products($crawler, 'table.box_width_cont center');
                        //$next = $crawler->filter('a.pageResults[title=" Siguiente "]');
                    }
                    //$this->output->writeln("<info>" . "Total productos: " . $this->count . "</info>");
                }
            }
        } else {
            $this->output->writeln("No hay subcategorías.");
        }
    }

    protected function products($crawler, $xpath) {
        $filter = $crawler->filter($xpath);
        if (iterator_count($filter)) {
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);

                $title = $crawler->filter('strong a')->text();
                $price = $crawler->filter('span')->text();
                $img = $this->domain2 . '/' . $crawler->filter('table.c2 a img')->attr('src');
                //$img_local = $crawler->filter('table.c2 a img')->attr('src');
                //file_put_contents($img_local, file_get_contents($img));

                $title = str_replace('NEW', '', $title);
                $title = str_replace('  ', ' ', $title);
                $title = trim($title);
                $title = preg_replace('/^\s/','',$title);
                if($this->input->getOption('handle') == true) {
                    $title = $this->subcategoria . " " . $title;
                }
                $title = str_replace('  ', ' ', $title);
                $price = substr_replace($price, "", -2);

                $this->products[$this->count]['Handle'] = $this->sluggify($title);
                $this->products[$this->count]['Title'] = $title;
                $this->products[$this->count]['Body'] = $title;
                $this->products[$this->count]['Vendor'] = 'eleggua';
                $this->products[$this->count]['Type'] = $this->subcategoria;
                $this->products[$this->count]['Tags'] = '';
                $this->products[$this->count]['Published'] = 'TRUE';
                $this->products[$this->count]['Option1 Name'] = 'Title';
                $this->products[$this->count]['Option1 Value'] = $title;
                $this->products[$this->count]['Option2 Name'] = '';
                $this->products[$this->count]['Option2 Value'] = '';
                $this->products[$this->count]['Option3 Name'] = '';
                $this->products[$this->count]['Option3 Value'] = '';
                $this->products[$this->count]['Variant SKU'] = $this->sluggify($title);
                $this->products[$this->count]['Variant Grams'] = '';
                $this->products[$this->count]['Variant Inventory Tracker'] = 'shopify';
                $this->products[$this->count]['Variant Inventory Qty'] = 100;
                $this->products[$this->count]['Variant Inventory Policy'] = 'deny';
                $this->products[$this->count]['Variant Fulfillment Service'] = 'manual';
                $this->products[$this->count]['Variant Price'] = $price;
                $this->products[$this->count]['Variant Compare At Price'] = '';
                $this->products[$this->count]['Variant Requires Shipping'] = 'TRUE';
                $this->products[$this->count]['Variant Taxable'] = 'TRUE';
                $this->products[$this->count]['Variant Barcode'] = '';
                $this->products[$this->count]['Image Src'] = $img;
                $this->products[$this->count]['Image Alt Text'] = $title;
                $this->count++;
                $this->output->writeln($title . ' ' . $price . ' ' . $img);
            }
        } else {
            $this->output->writeln("No hay productos.");
        }
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
    
    protected function writeCsv($products, $file = 'products.csv') {
        $fp = fopen('/var/www/edemy.es/d/' . $this->sluggify($file) . '.csv', 'w');
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
            'Variant Barcode',
            'Image Src',
            'Image Alt Text',
        ),',');
        foreach ($products as $product) {
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
                $product['Variant Barcode'], //Variant Barcode
                $product['Image Src'], //Image Src
                $product['Image Alt Text'], //Image Alt Text
                //str_replace(';', '', $linea['direccion']),
                //str_replace('"', '', (str_replace('=', '', $linea['cp']))),
            ),',');
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
        $a = array('À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','Ð','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','?','?','J','j','K','k','L','l','L','l','L','l','?','?','L','l','N','n','N','n','N','n','?','O','o','O','o','O','o','Œ','œ','R','r','R','r','R','r','S','s','S','s','S','s','Š','š','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Ÿ','Z','z','Z','z','Ž','ž','?','ƒ','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','?','?','?','?','?','?');
        $b = array('A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','s','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','o','u','u','u','u','y','y','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','D','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','IJ','ij','J','j','K','k','L','l','L','l','L','l','L','l','l','l','N','n','N','n','N','n','n','O','o','O','o','O','o','OE','oe','R','r','R','r','R','r','S','s','S','s','S','s','S','s','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Y','Z','z','Z','z','Z','z','s','f','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','A','a','AE','ae','O','o');
        return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/','/[ -]+/','/^-|-$/'),array('','-',''),str_replace($a,$b,$str)));
    }    
}
