<?php

namespace eDemy\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class pecCommand extends ContainerAwareCommand
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
            ->setName('get:pec')
            ->setDescription('Show HTML Response')
            ->addArgument('host', InputArgument::REQUIRED, 'What URL do you want to get?')
            ->addArgument('doc_host', InputArgument::REQUIRED, 'What URL do you want to get?')
            ->addOption('getCategories', null, InputOption::VALUE_OPTIONAL, 'Get Categories')
            ->addOption('getMaxCategories', null, InputOption::VALUE_OPTIONAL, 'Number of categories to crawl')
            ->addOption('getSubcategories', null, InputOption::VALUE_OPTIONAL, 'Get subcategories?')
            ->addOption('firstSubcategory', null, InputOption::VALUE_OPTIONAL, 'Number of subcategories to crawl')
            ->addOption('maxSubcategories', null, InputOption::VALUE_OPTIONAL, 'Number of subcategories to crawl')
            ->addOption('getProducts', null, InputOption::VALUE_OPTIONAL, 'Get products?')
            ->addOption('getCategoryProducts', null, InputOption::VALUE_OPTIONAL, 'Get category products?')
            ->addOption('writeAllSubcategories', null, InputOption::VALUE_OPTIONAL, 'Write all subcategories file?')
            ->addOption('writeSubcategories', null, InputOption::VALUE_OPTIONAL, 'Write subcategories files?')
            ->addOption('ignoreSubcategories', null, InputOption::VALUE_OPTIONAL, 'Ignore subcategories')

            ->addOption('last', null, InputOption::VALUE_OPTIONAL, 'Remove last characters from category?')
            ->addOption('handle', null, InputOption::VALUE_OPTIONAL, 'Append subcategory to handle?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->count = 0;
        $this->subcount = 0;
        $this->host = $this->input->getArgument('host') . '/';
        $this->doc_host = $this->input->getArgument('doc_host');
        $this->domain = $this->host;
        $this->domain2 = $this->doc_host . '/d';
        $this->products = array();
        $this->client = new Client();
        $crawler = $this->follow($this->domain);

        $this->loadPrices();
        //die();

        $this->getMaxCategories = $this->input->getOption('getMaxCategories');
        $this->getCategories = $this->input->getOption('getCategories');
        $this->getSubcategories = $this->input->getOption('getSubcategories');
        $this->maxSubcategories = null;
        $this->maxSubcategories = $this->input->getOption('maxSubcategories');
        $this->firstSubcategory = 1;
        $this->firstSubcategory = $this->input->getOption('firstSubcategory');
        $this->firstSubcategory--;
        $this->subcategory_count = 1;
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
            $this->getCategoryProducts = $this->input->getOption('getCategoryProducts');
            $this->categorias($crawler, '.glossymenu');
        }
    }

    protected function categorias($crawler, $xpath) {
        //categor�as sin subcategor�as
        $original_crawler = $crawler;
        $filter = $crawler->filter($xpath)
			->children();
            //->reduce(function (Crawler $node, $i) {
            //    return !strpos($node->attr('class'), "submenuheader");
            //});
		//die(var_dump($filter->eq(0)->attr('class')));
        $this->categoria = null;
        if (iterator_count($filter)) {
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if(strstr($crawler->attr('class'), "menuitem")) {
					$categoria = 1;
					$this->categoria = $this->trim('/\((\d+)\)/', $crawler->text(), 0);
					if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
							$this->output->writeln('<info>' . $this->subcategory_count . ' CAT: ' . $this->categoria . '</info>');
					}
					$this->subcategoria = null;
					$link = $crawler->attr('href');
					$link = array_values(explode('?', $link))[0];
					$crawler = $this->follow($link);

                    if(!in_array($this->subcategory_count, $this->ignoreSubcategories)) {
                        $this->products($crawler);
                    }
					//$this->subcategory_count++;
					if($this->maxSubcategories <= $this->subcategory_count++) {
						break;
					}
				} elseif (strstr($crawler->attr('class'), "submenuheader") ) {
					$categoria = 2;
					$this->categoria = $this->trim('/\((\d+)\)/', $crawler->text(), 0);
					if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                        $this->output->writeln('<info>' . $this->subcategory_count . ' CAT: ' . $this->categoria . '</info>');
					}
					$this->subcategoria = null;
				} else {
					$categoria = 3;
					$filter = $crawler->filter('a');
					if (iterator_count($filter)) {
						foreach ($filter as $j => $content) {
							$crawler = new Crawler($content);
							$this->subcategoria = $crawler->text();
							if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
								$this->output->writeln('<info>' . $this->subcategory_count . ' SUBCAT: ' . $this->subcategoria . '</info>');
							}
							
							$link = $crawler->attr('href');
							$link = array_values(explode('?', $link))[0];
							$crawler = $this->follow($link);
                            if(!in_array($this->subcategory_count, $this->ignoreSubcategories)) {
                                $this->products($crawler);
                            }
							//$this->subcategory_count++;
							if($this->maxSubcategories <= $this->subcategory_count++) {
								break;
							}

							if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
								$this->output->writeln("<question>" . "Total productos: " . $this->count . "</question>");
							}
						}
					}
				}
				if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
					$this->output->writeln("<question>" . "Total productos: " . $this->count . "</question>");
				}
            }
        }
		if ($this->writeAllSubcategories) {
			$this->writeCsv($this->products, 'allsubcategories');
			empty($this->products);
		}
    }

    protected function products($crawler) {
		$filter = $crawler->filter('td.productListing-data');
		if (iterator_count($filter)) {
			foreach ($filter as $i => $content) {
				$crawler = new Crawler($content);
				if(iterator_count($crawler->filter('a')) > 1) {
					$link = $crawler->filter('a')->eq(0)->attr('href');
					$link = array_values(explode('?', $link))[0];
					$crawler = $this->follow($link);
					$this->product($crawler);
				}
			}
		}
		
		return true;
	}



    protected function product($crawler) {
		$name = trim($crawler->filter('form table h1')->eq(0)->text());
		$name = $this->trim('/(\[\])/', $name, 0);
		$price = trim($crawler->filter('form table h1')->eq(1)->text());
		$description = $crawler->filter('td.main p')->eq(0)->html();
		$img = null;
		//die($this->output->writeln($crawler->html()));
		$img = array_values(explode('?', $crawler->filter('td.main noscript a')->eq(0)->attr('href')))[0];
		$talla = 'normal';
		if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$this->output->writeln('<comment>' . $name . '</comment>' . ' ' . $talla . ' ' . $img);
		}

		if(iterator_count($crawler->filter('table.stock'))) {
			$filter = $crawler->filter('table.stock tr');
			//die(var_dump($filter->html()));
			foreach ($filter as $j => $content) {
				if($j) {
					$crawler = new Crawler($content);
					$talla = $crawler->filter('td.infoBoxContents')->eq(0)->text();
					$price = $crawler->filter('td.infoBoxContents')->eq(1)->text();
					$stock = $crawler->filter('td.infoBoxContents')->eq(2)->text();
					if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
						$this->output->writeln('<info>' . $talla . ' ' . $price . ' ' . $stock . '</info>');
					}

                    //$pvp = $this->getPvp($number);
                    $this->products[$this->count]['Handle'] = $this->sluggify($name);
                    $this->products[$this->count]['Title'] = $name;
                    $this->products[$this->count]['Body'] = $description;
                    $this->products[$this->count]['Vendor'] = 'peces';

                    $type = $this->categoria;
                    if($this->subcategoria) $type .= ' - ' . $this->subcategoria;
                    $this->products[$this->count]['Type'] = $type;
                    $this->products[$this->count]['Tags'] = $type . ",";
                    if($price) {
                        $this->products[$this->count]['Published'] = 'TRUE';
                    } else {
                        $this->products[$this->count]['Published'] = 'FALSE';
                    }
                    $this->products[$this->count]['Option1 Name'] = 'Talla';
                    $this->products[$this->count]['Option1 Value'] = trim($talla);
                    $this->products[$this->count]['Option2 Name'] = null;
                    $this->products[$this->count]['Option2 Value'] = null;
                    $this->products[$this->count]['Option3 Name'] = null;
                    $this->products[$this->count]['Option3 Value'] = null;
                    $this->products[$this->count]['Variant SKU'] = $name;
                    $this->products[$this->count]['Variant Grams'] = 500;//$this->getPeso($number, $size, 'peso');
                    $this->products[$this->count]['Variant Inventory Tracker'] = null; //'shopify';
                    $stock = preg_replace("/[^0-9]/","", $stock);
                    if(is_numeric($stock)) {
                        $this->products[$this->count]['Variant Inventory Qty'] = (int) $stock;
                    } else {
                        
                        $this->products[$this->count]['Variant Inventory Qty'] = $stock;
                    }
                    $this->products[$this->count]['Variant Inventory Policy'] = 'continue';
                    $this->products[$this->count]['Variant Fulfillment Service'] = 'manual';
                    $price = preg_replace("/(EUR)/","", $price);
                    $price = preg_replace("/[^0-9.]/","", $price);
                    $price = (float) $price;
                    $price = round($price) - 0.05;
                    //die(var_dump($price));
                    //$price = trim(preg_replace("/\./",",", $price));
                    //die(var_dump(trim($price)));
                    //die(var_dump(money_format('%i', (float) $price)));
                    $this->products[$this->count]['Variant Price'] = $price;
                    $this->products[$this->count]['Variant Compare At Price'] = null;
                    $this->products[$this->count]['Variant Requires Shipping'] = 'TRUE';
                    $this->products[$this->count]['Variant Taxable'] = 'TRUE';
                    //$this->products[$this->count]['Variant Barcode'] = null;
                    if($img) {
                        $img_file = explode('/', $img);
                        $img_file = end($img_file);
                        $img_local = "/var/www/" . $this->doc_host . "/www/web/d/peces/original/" . $img_file;
                        $img_local_resized = "/var/www/" . $this->doc_host . "/www/web/d/peces/resized/" . $img_file;
                        $img_edemy = "http://" . $this->doc_host . "/d/peces/resized/" . $img_file;
                        //die(var_dump($img_local));
                        //die(var_dump($img_local)));
                        file_put_contents($img_local, file_get_contents($img));
                        //file_put_contents("/var/www/edemy.es/www/web/d/peces/original/" . $img_local, file_get_contents($img));
                        $iImage = new \Imagick($img_local);
                        $iImage->trimImage(0);
                        
                        $iImage->scaleImage(600,0,false);
                        //$iImage->setImageBackgroundColor('white');
                        //$iImage->extentImage(600,600,0,0);
                        //$iImage->resizeImage(600,0,\Imagick::FILTER_LANCZOS,1,false);
                        $iImage->writeImage($img_local);
                        $iImage->clear();
                        $iImage->destroy(); 
                        $this->products[$this->count]['Image Src'] = $img_edemy;
                        $this->products[$this->count]['Image Alt Text'] = $name;
                        $this->products[$this->count]['Variant Image'] = $img_edemy;
                    } else {
                        $this->products[$this->count]['Image Src'] = null;
                        $this->products[$this->count]['Image Alt Text'] = null;
                        $this->products[$this->count]['Variant Image'] = null;
                    }
                    $this->products[$this->count]['Variant Weight Unit'] = 'g';//$this->getPeso($number, $size, 'unidad');
                    $this->count++;



				}
			}
		} else {
			$stock = trim($crawler->filter('form table td[valign="top"]')->eq(2)->text());


            //$pvp = $this->getPvp($number);
            $this->products[$this->count]['Handle'] = $this->sluggify($name);
            $this->products[$this->count]['Title'] = $name;
            $this->products[$this->count]['Body'] = $description;
            $this->products[$this->count]['Vendor'] = 'peces';

            $type = $this->categoria;
            if($this->subcategoria) $type .= ' - ' . $this->subcategoria;
            $this->products[$this->count]['Type'] = $type;
            $this->products[$this->count]['Tags'] = $type . ",";
            if($price) {
                $this->products[$this->count]['Published'] = 'TRUE';
            } else {
                $this->products[$this->count]['Published'] = 'FALSE';
            }
            $this->products[$this->count]['Option1 Name'] = 'Talla';
            $this->products[$this->count]['Option1 Value'] = trim($talla);
            $this->products[$this->count]['Option2 Name'] = null;
            $this->products[$this->count]['Option2 Value'] = null;
            $this->products[$this->count]['Option3 Name'] = null;
            $this->products[$this->count]['Option3 Value'] = null;
            $this->products[$this->count]['Variant SKU'] = $name;
            $this->products[$this->count]['Variant Grams'] = 500;//$this->getPeso($number, $size, 'peso');
            $this->products[$this->count]['Variant Inventory Tracker'] = null; //'shopify';
            $stock = preg_replace("/[^0-9]/","", $stock);
            if(is_numeric($stock)) {
                $this->products[$this->count]['Variant Inventory Qty'] = (int) $stock;
            } else {
                
                $this->products[$this->count]['Variant Inventory Qty'] = $stock;
            }
            $this->products[$this->count]['Variant Inventory Policy'] = 'continue';
            $this->products[$this->count]['Variant Fulfillment Service'] = 'manual';
            $price = preg_replace("/[^0-9.]/","", $price);
            $price = preg_replace("/(EUR)/","", $price);
            $price = (float) $price;
            $price = round($price) - 0.05;
            //$price = trim(preg_replace("/\./",",", $price));
            //die(var_dump(trim($price)));
            //die(var_dump(money_format('%i', (float) $price)));
            $this->products[$this->count]['Variant Price'] = $price;
            $this->products[$this->count]['Variant Compare At Price'] = null;
            $this->products[$this->count]['Variant Requires Shipping'] = 'TRUE';
            $this->products[$this->count]['Variant Taxable'] = 'TRUE';
            //$this->products[$this->count]['Variant Barcode'] = null;
            if($img) {
                $img_file = explode('/', $img);
                $img_file = end($img_file);
                $img_local = "/var/www/" . $this->doc_host . "/www/web/d/peces/original/" . $img_file;
                $img_local_resized = "/var/www/" . $this->doc_host . "/www/web/d/peces/resized/" . $img_file;
                $img_edemy = "http://" . $this->doc_host . "/d/peces/resized/" . $img_file;
                //die(var_dump($img_local));
                //die(var_dump($img_local)));
                file_put_contents($img_local, file_get_contents($img));
                //file_put_contents("/var/www/edemy.es/www/web/d/peces/original/" . $img_local, file_get_contents($img));
                $iImage = new \Imagick($img_local);
                $iImage->trimImage(0);
                $iImage->scaleImage(600,0,false);
                //$iImage->setImageBackgroundColor('white');
                //$iImage->extentImage(600,600,0,0);
                
                $iImage->resizeImage(600,0,\Imagick::FILTER_LANCZOS,1,false);
                $iImage->writeImage($img_local);
                $iImage->clear();
                $iImage->destroy(); 
                $this->products[$this->count]['Image Src'] = $img_edemy;
                $this->products[$this->count]['Image Alt Text'] = $name;
                $this->products[$this->count]['Variant Image'] = $img_edemy;
            } else {
                $this->products[$this->count]['Image Src'] = null;
                $this->products[$this->count]['Image Alt Text'] = null;
                $this->products[$this->count]['Variant Image'] = null;
            }
            $this->products[$this->count]['Variant Weight Unit'] = 'g';//$this->getPeso($number, $size, 'unidad');
            $this->count++;

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
    
    protected function writeCsv($products, $file) {
        $fp = fopen("/var/www/" . $this->doc_host . "/www/web/d/peces/csv/" . $file . '.csv', 'w');
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
                            //die("a");
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
        if (($handle = fopen("/var/www/" . $this->doc_host . "/www/web/d/peces/csv/precios_peces.csv", "r")) !== FALSE) {
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
