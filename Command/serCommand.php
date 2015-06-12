<?php

namespace eDemy\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class serCommand extends ContainerAwareCommand
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
            ->setName('get:ser')
            ->setDescription('Show HTML Response')
            ->addArgument('url', InputArgument::OPTIONAL, 'What URL do you want to get?')
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
        $this->count = 0;
        $this->subcount = 0;
        $this->host = 'http://www.__.de/';
        $this->domain = 'http://www.__.de/es/productos.html';
        $this->domain2 = 'http://www.__.es/d';
        $this->products = array();
        $this->client = new Client();
        $crawler = $this->follow($this->domain);
        $this->output = $output;
        $this->input = $input;

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
            $this->categorias($crawler, '.productworld .menu-level1');
        }
    }

    protected function categorias($crawler, $xpath) {
        
        $continue = true;
        $original_crawler = $crawler;
        if($this->getMaxCategories != null) {
            $filter = $crawler->filter($xpath)->eq($this->getMaxCategories);
        } else {
            $filter = $crawler->filter($xpath);
        }
        if (iterator_count($filter)) {
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($this->input->getOption('last') == true) {
                    $this->categoria = $this->trim('/\((\d+)\)/', $crawler->filter('a')->text(), -2);
                } else {
                    $this->categoria = $this->trim('/\((\d+)\)/', $crawler->filter('a')->text(), 0);
                }
$this->cat = $this->categoria;
                if($this->getCategoryProducts) {
                    $crawler = $this->follow($crawler->filter('a')->attr('href'));
                    $this->products($crawler, '.producto');
                    $next = $crawler->filter('a.pageResults[title=" Siguiente "]');
                    //die(var_dump(iterator_count($next)));
                    while(iterator_count($next) == 2) {
                        $crawler = $this->follow($next->attr('href'));
                        $this->products($crawler, '.producto');
                        $next = $crawler->filter('a.pageResults[title=" Siguiente "]');
                    }
                    if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                        $this->output->writeln("<question>" . "Total productos: " . $this->count . ' - ' . $this->subcategoria . "</question>");
                    }
                } else {
                    if($this->getSubcategories and $continue) {
                        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                            $this->output->writeln('<info>' . 'CAT: ' . $this->categoria . '</info>');
                        }
                        $link = $this->host . $crawler->filter('a')->attr('href');
                        $crawler = $this->follow($link);
                        //die(var_dump($link));
                        $continue = $this->subcategorias($crawler, '.productworld .menu-level2', '.productworld .menu-level3');
                    }
                }
            }
            if ($this->writeAllSubcategories) {
                $this->writeCsv($this->products, 'allsubcategories');
                empty($this->products);
            }
        } else {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $this->output->writeln("No hay categorías.");
            }
        }
    }

    protected function subcategorias($crawler, $xpath, $xpath2 = null) {
        $hassubcategories = true;
        $filter = $crawler->filter($xpath);
        if (iterator_count($filter)) {
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($this->input->getOption('last') == true) {
                    $this->subcategoria = $this->trim('/\((\d+)\)/', $crawler->filter('a')->text(), -2);
                } else {
                    $this->subcategoria = $this->trim('/\((\d+)\)/', $crawler->filter('a')->text(), 0);
                }                

                if($xpath2 == null) {
                    if($this->subcategory_count >= $this->firstSubcategory) {
                        
                        
                        if($this->maxSubcategories) {
                            if(($this->subcategory_count - $this->firstSubcategory) < $this->maxSubcategories) {
                                if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                                    $this->output->writeln('<info>' . strval($this->subcategory_count + 1) . ' SUBSUBCAT: ' . $this->subcategoria . '</info>');
                                }

                                if($this->getProducts) {
                                    $crawler = $this->follow($this->host . $crawler->filter('a')->attr('href'));
                                    //$filter = $crawler->filter('.productList-item .productTitle');
                                    //die(var_dump(iterator_count($filter)));
                                    //if (iterator_count($filter)) {
                                    //    foreach ($filter as $i => $content) {
                                    if(!in_array($this->subcategory_count + 1, $this->ignoreSubcategories)) {
                                        $this->products($crawler, $this->subcategoria);
                                        //    }
                                        //}
                                        //die();
                                        //$this->products($crawler, '.producto');
                                        
                                        /*
                                        $next = $crawler->filter('a[title="Siguiente »"]');
                                        //die(var_dump(iterator_count($next)));
                                        while(iterator_count($next) == 2) {
                                            $crawler = $this->follow($next->attr('href'));
                                            $this->products($crawler, '.productList-item');
                                            $next = $crawler->filter('a[title="Siguiente »"]');
                                        }
                                        * */
                                        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                                            $this->output->writeln("<question>" . "Productos: "  . $this->subcount . " - " . $this->subcategoria . " - Total productos: " . $this->count . "</question>");
                                        }
                                        $this->subcount = 0;
                                    }
                                }
                            } else {
                                return false;
                            }
                        }
                    }
                    if(
                        ($this->writeSubcategories) and 
                        ($this->firstSubcategory  <= $this->subcategory_count) and
                        (($this->firstSubcategory + $this->maxSubcategories - 1) >= $this->subcategory_count)) 
                    {
                        if(!in_array($this->subcategory_count + 1, $this->ignoreSubcategories)) {
                            $this->writeCsv($this->products, sprintf('%02d', $this->subcategory_count + 1) . ' - ' . $this->categoria . ' - ' . $this->subcategoria);
                            $this->products = empty($this->products);
                            //$this->output->writeln(count($this->products));
                            //$this->products = array();
                        }
                    }
                    if(!$this->writeAllSubcategories) {
                        //die();
                    }
                    $this->subcategory_count++;

                } elseif($hassubcategories) {
                    if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                        $this->output->writeln('<info>' . 'SUBCAT: ' . $this->subcategoria . '</info>');
                    }
                    $this->categoria = $this->cat . ' - ' . $this->subcategoria;

                    $link = $this->host . $crawler->filter('a')->attr('href');
                    $crawler = $this->follow($link);
                    //die(var_dump($link));
                    $hassubcategories = $this->subcategorias($crawler, '.productworld .menu-level3');

                } else {
                    return false;
                }
            }
        } else {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $this->output->writeln("No hay subcategorías.");
            }
            return true;
        }
        return true;
    }

    protected function products($crawler, $extra = null) {
        if( ($crawler->filter('.productList-item .productTitle')->count()) and 
            ($crawler->filter('.category_list_row')->count() > 1)) {
            //if($this->subcategory_count == 8) die("a");
            $filter = $crawler->filter('.productList-item .productTitle');
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($crawler->filter('.productTitle a')->count()) {
                    $link = $this->host . $crawler->filter('.productTitle a')->attr('href');
                }
                $crawler = $this->follow($link);
                $this->product($crawler);
            }
        } elseif( ($crawler->filter('.productList-item .productTitle')->count()) and 
            ($crawler->filter('.category_list_row')->count() <= 1)) {
            //if($this->subcategory_count == 8) die("a");
            $filter = $crawler->filter('.productList-item .productTitle');
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($crawler->filter('.productTitle a')->count()) {
                    $link = $this->host . $crawler->filter('.productTitle a')->attr('href');
                }
                $crawler = $this->follow($link);
                $this->product($crawler);
            }
        } elseif(
            ($crawler->filter('.productList-item-float .productTitle')->count()) and
            ($crawler->filter('.category_list_row')->count() <= 1)) {
                //die("b");
            $filter = $crawler->filter('.productList-item-float .productTitle');
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($crawler->filter('.productTitle a')->count()) {
                    $link = $this->host . $crawler->filter('.productTitle a')->attr('href');
                }
                $crawler = $this->follow($link);
                $this->product($crawler);
            }
        } elseif($crawler->filter('.category_list_row')->count()) {
            //die(var_dump("c"));
            $filter = $crawler->filter('.category_list_row');
            foreach ($filter as $i => $content) {
                if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $this->output->writeln("a" . var_dump($i));
                }
                $crawler = new Crawler($content);
                $cat_title = null;
                if($crawler->filter('.category_title a')->count() > 1) {
                    $cat_title = trim($crawler->filter('.category_title a')->eq(1)->text());
                    //die(var_dump($cat_title));
                } elseif($crawler->filter('.category_title a')->count() == 1) {
                    $cat_title = trim($crawler->filter('.category_title a')->text());
                    //die(var_dump($cat_title));
                } elseif($crawler->filter('.catTitle a')->count() > 1) {
                    $cat_title = trim($crawler->filter('.catTitle a')->eq(1)->text());
                }
                if($cat_title == $extra) {
                    //die(var_dump($extra));
                    if($crawler->filter('.singProd')->count()) {
                        $filter = $crawler->filter('.singProd');
                        $path = ".singProdImg a";
                    } elseif($crawler->filter('.catTitleStepSingleProd')->count()) {
                        $filter = $crawler->filter('.catTitleStepSingleProd');
                        $path = ".productImage a";
                    } elseif($crawler->filter('.productList-item')->count()) {
                        $filter = $crawler->filter('.productList-item');
                        $path = ".productImage a";
                    } elseif($crawler->filter('.productList-item-float')->count()) {
                        $filter = $crawler->filter('.productList-item-float');
                        $path = ".productImage a";
                    }
                    if($filter->count()) {
                        foreach ($filter as $i => $content) {
                            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                                $this->output->writeln("b" . var_dump($i));
                            }
                            $crawler = new Crawler($content);
                            if($crawler->filter($path)->count()) {
                                //die(var_dump("d"));
                                $link = $this->host . $crawler->filter($path)->attr('href');
                                $crawler = $this->follow($link);
                                $this->product($crawler);
                            }
                        }
                    }
                }
                if (
                    ($this->subcategory_count == 32) or //análisis del agua
                    ($this->subcategory_count == 33) or //iluminación
                    ($this->subcategory_count == 34) or //calefacción
                    ($this->subcategory_count == 36) or //bombas
                    ($this->subcategory_count == 37) //varios
                ){
                    if($crawler->filter('.productList-item-float')->count()) {
                        $filter = $crawler->filter('.productList-item-float');
                        $path = ".productImage a";
                    }
                    if($filter->count()) {
                        foreach ($filter as $i => $content) {
                            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                                $this->output->writeln("b" . var_dump($i));
                            }
                            $crawler = new Crawler($content);
                            if($crawler->filter($path)->count()) {
                                //die(var_dump("d"));
                                $link = $this->host . $crawler->filter($path)->attr('href');
                                $crawler = $this->follow($link);
                                $this->product($crawler);
                            }
                        }
                    }
                }
            }
        } elseif($crawler->filter('.catTitleStepSingleProd')->count()) {
            $filter = $crawler->filter('.catTitleStepSingleProd');
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($crawler->filter('.productImage a')->count()) {
                    $link = $this->host . $crawler->filter('.productImage a')->attr('href');
                    $crawler = $this->follow($link);
                    $this->product($crawler);
                }
            }
        } elseif($crawler->filter('.catTitleStepProd')->count()) {
            $filter = $crawler->filter('.catTitleStepProd');
            foreach ($filter as $i => $content) {
                $crawler = new Crawler($content);
                if($crawler->filter('.productImage a')->count()) {
                    $link = $this->host . $crawler->filter('.productImage a')->attr('href');
                    $crawler = $this->follow($link);
                    $this->product($crawler);
                }
            }
        } else {
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $this->output->writeln("No hay productos.");
            }
        }
    }
    
    protected function product($crawler) {
        $join = "";
        
        //// TITLE
        $title = $crawler->filter('.tx-seraproductcatalog-pi1 h1')->each(function (Crawler $node, $i) {
            return $node->text();
        });
        $title = implode($join, $title);

        //// BODY
        $subtitle = $crawler->filter('#subtitle p')->each(function (Crawler $node, $i) {
            return "<p>" . $node->text() . "</p>";
        });
        $subtitle = implode($join, $subtitle);
        $bodytext = $crawler->filter('#bodytext p')->each(function (Crawler $node, $i) {
            return "<p>" . $node->text() . "</p>";
        });
        $bodytext = implode($join, $bodytext);

        $description = $crawler->filter('#information_content #description')->each(function (Crawler $node, $i) {
            return "<h3>" . $node->text() . "</h3>";
        });
        $description = implode($join, $description);

        $feedingnote = $crawler->filter('#information_content #feedingnote dd')->each(function (Crawler $node, $i) {
            return "<p>" . $node->text() . "</p>";
        });
        $feedingnote = implode($join, $feedingnote);

        $composition = $crawler->filter('#information_content #composition dd')->each(function (Crawler $node, $i) {
            return "<p>" . $node->text() . "</p>";
        });
        $composition = implode($join, $composition);

        $qualityanalysis = $crawler->filter('#information_content #qualityanalysis dd')->each(function (Crawler $node, $i) {
            return "<p>" . $node->text() . "</p>";
        });
        $qualityanalysis = implode($join, $qualityanalysis);

        $additives = $crawler->filter('#information_content #additives dd')->each(function (Crawler $node, $i) {
            return "<p>" . $node->text() . "</p>";
        });
        $additives = implode($join, $additives);

        $body = $subtitle;
        $body .= $join . $bodytext;
        $body .= $join . $description;
        if($feedingnote) {
            $body .= $join . "<h3>Modo de empleo:</h3>";
            $body .= $join . $feedingnote;
        }
        if($composition) {
            $body .= $join . "<h3>Composición:</h3>";
            $body .= $join . $composition;
        }
        if($qualityanalysis) {
            $body .= $join . "<h3>Análisis de Calidad:</h3>";
            $body .= $join . $qualityanalysis;
        }
        if($additives) {
            $body .= $join . "<h3>Aditivos:</h3>";
            $body .= $join . $additives;
        }

        ////
        //die(var_dump($crawler->filter('#articles tr')->count()));
        if($crawler->filter('#articles tr')->count()) {
            $filter = $crawler->filter('#articles tr');
            foreach ($filter as $i => $content) {
                if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $this->output->writeln("c" . var_dump($i));
                }
                if($i) {
                    $subcrawler = new Crawler($content);
                    $size = $subcrawler->filter('.size')->text();
                    $number = (int) trim($subcrawler->filter('.number')->text());


                    $return = false;
                    if($this->products) {
                        foreach($this->products as $j => $p) {
                            if((int) $p['Variant SKU'] == $number) {
                                //if($p['Type'] != $this->categoria . ' - ' . $this->subcategoria) {
                                    $this->products[$j]['Tags'] .= $this->categoria . ' - ' . $this->subcategoria . ',';
                                    $return = true;
                                    //die(var_dump($i));
                                //}
                            }
                        }
                    }
                    if($return) {
                        return true;
                    }


                    $comment = $subcrawler->filter('.comment')->text();
                    $img = null;
                    if($crawler->filter('#miniimages .miniimage_single')->eq($i - 1)->filter('a')->count()) {
                        $img = $crawler->filter('#miniimages .miniimage_single')->eq($i - 1)->filter('a')->attr('href');
                    }
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
                    $this->products[$this->count]['Option1 Name'] = 'Tamaño';
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
                        //die(var_dump($img_local)));
                        file_put_contents("/var/www/__.es/www/web/d/sera/original/" . $img_local, file_get_contents($img));
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
                }
            }
        } else {
            $this->count++;
            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $this->output->writeln("No hay variaciones");
            }
        }
        /*
        $price = $crawler->filter('.foto a div div')->text();
        $img = $this->domain2 . '/' . $crawler->filter('table.c2 a img')->attr('src');
        $img_local = $crawler->filter('table.c2 a img')->attr('src');
        file_put_contents($img_local, file_get_contents($img));
        $title = str_replace('NEW', '', $title);
        $title = str_replace('  ', ' ', $title);
        $title = trim($title);
        $title = preg_replace('/^\s/','',$title);
        if($this->input->getOption('handle') == true) {
            $title = $this->subcategoria . " " . $title;
        }
        $title = str_replace('  ', ' ', $title);
        $price = substr_replace($price, "", -2);
        * */
        
        //$this->output->writeln($title . ' ' . $price . ' ' . $img);

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
        $fp = fopen("/var/www/__.es/www/web/d/sera/csv/" . $file . '.csv', 'w');
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
        $a = array('À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','Ð','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','?','?','J','j','K','k','L','l','L','l','L','l','?','?','L','l','N','n','N','n','N','n','?','O','o','O','o','O','o','Œ','œ','R','r','R','r','R','r','S','s','S','s','S','s','Š','š','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Ÿ','Z','z','Z','z','Ž','ž','?','ƒ','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','?','?','?','?','?','?');
        $b = array('A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','s','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','o','u','u','u','u','y','y','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','D','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','IJ','ij','J','j','K','k','L','l','L','l','L','l','L','l','l','l','N','n','N','n','N','n','n','O','o','O','o','O','o','OE','oe','R','r','R','r','R','r','S','s','S','s','S','s','S','s','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Y','Z','z','Z','z','Z','z','s','f','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','A','a','AE','ae','O','o');
        return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/','/[ -]+/','/^-|-$/'),array('','-',''),str_replace($a,$b,$str)));
    }

    private function loadPrices() {
        if (($handle = fopen("/var/www/__.es/www/web/d/sera/csv/precios_sera.csv", "r")) !== FALSE) {
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
