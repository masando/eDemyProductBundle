<?php

namespace eDemy\ProductBundle\Command;

use GuzzleHttp\Cookie\SetCookie;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Process;
use GuzzleHttp\Client;

//use GuzzleHttp\Cookie\CookieJar;

class azCommand extends ContainerAwareCommand
{
    private $client;
    private $h, $url, $dh;
    private $c, $gc, $fc, $mc, $gcp, $ic, $c_count, $wc;
    private $s, $gs, $fs, $ms, $gsp, $is, $s_count, $ws;
    private $ss, $gss, $fss, $mss, $gssp, $iss, $ss_count, $wss;
    private $p, $fp, $mp;
    private $was;
    private $output, $input;
    private $prices;
    private $count, $subcount;
    private $jar;
    
    protected function configure()
    {
        $this
            ->setName('get:az')
            ->setDescription('Show HTML Response')

            ->addArgument('h', InputArgument::REQUIRED, 'What host do you want to get?')
            ->addArgument('url', InputArgument::REQUIRED, 'What URL do you want to get?')
            ->addArgument('dh', InputArgument::REQUIRED, 'uri to save images')

            ->addOption('gc', null, InputOption::VALUE_OPTIONAL, 'Get Categories')
            //->addOption('fc', null, InputOption::VALUE_OPTIONAL, 'First category to crawl', 1)
            ->addOption('mc', null, InputOption::VALUE_OPTIONAL, 'Number of categories to crawl', 9999)
            ->addOption('gcp', null, InputOption::VALUE_OPTIONAL, 'Get products from category?', 0)
            ->addOption('ic', null, InputOption::VALUE_OPTIONAL, 'Ignore categories')
            ->addOption('wc', null, InputOption::VALUE_OPTIONAL, 'Write categories files?')

            ->addOption('gs', null, InputOption::VALUE_OPTIONAL, 'Get subcategories?')
            //->addOption('fs', null, InputOption::VALUE_OPTIONAL, 'First subcategory to crawl', 1)
            ->addOption('ms', null, InputOption::VALUE_OPTIONAL, 'Number of subcategories to crawl', 9999)
            ->addOption('gsp', null, InputOption::VALUE_OPTIONAL, 'Get products from subcategory?', 0)
            ->addOption('is', null, InputOption::VALUE_OPTIONAL, 'Ignore subcategories')
            ->addOption('ws', null, InputOption::VALUE_OPTIONAL, 'Write subcategories files?')

            ->addOption('gss', null, InputOption::VALUE_OPTIONAL, 'Get subcategories?')
            //->addOption('fss', null, InputOption::VALUE_OPTIONAL, 'First subcategory to crawl', 1)
            ->addOption('mss', null, InputOption::VALUE_OPTIONAL, 'Number of subcategories to crawl', 9999)
            ->addOption('gssp', null, InputOption::VALUE_OPTIONAL, 'Get products from subsubcategory?', 0)
            ->addOption('iss', null, InputOption::VALUE_OPTIONAL, 'Ignore subsubcategories')
            ->addOption('wss', null, InputOption::VALUE_OPTIONAL, 'Write subsubcategories files?')

            ->addOption('fp', null, InputOption::VALUE_OPTIONAL, 'First product to crawl')
            ->addOption('mp', null, InputOption::VALUE_OPTIONAL, 'Number of products to crawl')

            ->addOption('was', null, InputOption::VALUE_OPTIONAL, 'Write all subcategories file?')
        ;
    }

    protected function getOptions() {
        $this->h = $this->input->getArgument('h');
        $this->url = $this->input->getArgument('url');
        $this->dh = $this->input->getArgument('dh');

        $this->gc = $this->input->getOption('gc');
        //$this->fc = $this->input->getOption('fc');
        $this->mc = $this->input->getOption('mc');
        $this->gcp = $this->input->getOption('gcp');
        $this->ic = explode(',', $this->input->getOption('ic'));
        $this->wc = $this->input->getOption('wc');

        $this->gs = $this->input->getOption('gs');
        //$this->fs = $this->input->getOption('fs');
        $this->ms = $this->input->getOption('ms');
        $this->gsp = $this->input->getOption('gsp');
        $this->is = explode(',', $this->input->getOption('is'));
        $this->ws = $this->input->getOption('ws');

        $this->gss = $this->input->getOption('gss');
        //$this->fss = $this->input->getOption('fss');
        $this->mss = $this->input->getOption('mss');
        $this->gssp = $this->input->getOption('gssp');
        $this->iss = explode(',', $this->input->getOption('iss'));
        $this->wss = $this->input->getOption('wss');

        $this->fp = $this->input->getOption('fp');
        $this->mp = $this->input->getOption('mp');

        $this->was = $this->input->getOption('was');
    }

    protected function init() {
        $this->c_count = 1;
        $this->s_count = 1;
        $this->ss_count = 1;
        $this->p_count = 1;
        $this->c = array();
        $this->s = array();
        $this->ss = array();
        $this->p = array();

        $this->categories_xpath = '#categories_block_left > div > ul';
        $this->category_item_xpath = 'a';
        $this->subcategories_xpath = 'ul';
        $this->subcategory_item_xpath = 'a';
        $this->subsubcategories_xpath = 'ul';
        $this->subsubcategory_item_xpath = 'a';
        $this->productlist_xpath = '.product_list';

        $igno = array();
        if (count($this->ic)) {
            foreach ($this->ic as $ic) {
                $igno[] = (int) $ic;
            }
            $this->ic = $igno;
        }

        $igno = array();
        if (count($this->is)) {
            foreach ($this->is as $is) {
                $igno[] = (int)$is;
            }
            $this->is = $igno;
        }

        $igno = array();
        if (count($this->iss)) {
            foreach ($this->iss as $iss) {
                $igno[] = (int) $iss;
            }
            $this->iss = $igno;
        }

        $this->client = new Client();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $this->getOptions();
        $this->init();
        //$res = $this->client->request('GET', 'http://azaria.es/login', [
        //
        //]);
        //echo $res->getStatusCode();
        //echo $res->getHeader('content-type');
        //echo $res->getBody();
        //die();
        $this->categories();

        //if ($this->ws) {
        //    $this->writeCsv($this->products, $this->subcategoria);
        //    empty($this->products);
        //}

//        if ($this->gss) {
//            $this->subsubcategories();
//        }

        //if ($this->was) {
        //    $this->writeCsv($this->p, 'allsubcategories');
        //    empty($this->p);
        //}

        return true;
    }

    protected function categories() {
        if ($this->gc) {
            $this->logV('Start categories crawling');
            $crawler = $this->follow($this->h.$this->url);
            $c_cats = $crawler->filter($this->categories_xpath)->children();
            if (iterator_count($c_cats)) {
                foreach ($c_cats as $i => $content) {
                    if (($this->gc <= $this->c_count) && (($this->gc + $this->mc - 1) >= $this->c_count)) {
                        $c_cat = new Crawler($content);
                        $item = $c_cat->filter($this->category_item_xpath);
                        $name = trim($item->text());
                        $url = $item->attr('href');
                        $this->c[$this->c_count]['id'] = $this->c_count;
                        $this->c[$this->c_count]['name'] = $name;
                        $this->c[$this->c_count]['url'] = $url;
                        $this->log($this->c_count.' CAT: '.$name, 'red');
                        $p = array();
                        if ($this->gs) {
                            $subcategories = $this->subcategories($this->c_count);
                            $this->c[$this->c_count]['s'] = $subcategories;
                            if ((count($subcategories) == 0) && ($this->gsp == 1)) {
                                $this->gcp = 1;
                            }
                            if ($this->gcp) {
                                $products = $this->products($url);
                                $this->c[$this->c_count]['p'] = $products;
                                foreach ($products as $item) {
                                    $p[] = $item;
                                }
                            }
                        }
                        if($this->wc) {
                            $c = $this->c[$this->c_count];
                            if(array_key_exists('p', $c)) {
                                foreach ($c['p'] as $item) {
                                    $p[] = $item;
                                }
                            }
                            foreach($c['s'] as $s) {
                                if(array_key_exists('p', $s)) {
                                    foreach ($s['p'] as $item) {
                                        $p[] = $item;
                                    }
                                }
                                foreach($s['ss'] as $ss) {
                                    if(array_key_exists('p', $ss)) {
                                        foreach ($ss['p'] as $item) {
                                            $p[] = $item;
                                        }
                                    }
                                }
                            }
                            $this->writeCsv($p, $this->c[$this->c_count]['name']);
                        }
                    }
                    $this->c_count++;
                }
            } else {
                if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                    $this->log("No categories.");
                }
            }
            $this->logV("End categories crawling");
        }
    }

    protected function subcategories($c_count) {
        $this->s = array();
        $this->logV('Start subcategories crawling');
        //$c_cat = $this->follow($this->c[$num]['url']);
        $c_cat = $this->follow($this->h . $this->url);
        $c_subcats = $c_cat->filter($this->categories_xpath)->children()->eq($c_count - 1)->filter($this->subcategories_xpath)->children();
        //$c_subcats = $c_cat->filter('ul')->first()->children();
        if (iterator_count($c_subcats)) {
            foreach ($c_subcats as $i => $content) {
                if(($this->gs <= $this->s_count) && (($this->gs + $this->ms - 1) >= $this->s_count)) {
                    $c_subcat = new Crawler($content);
                    $item = $c_subcat->filter($this->subcategory_item_xpath);
                    $name = trim($item->text());
                    $url = $item->attr('href');
                    $this->s[$this->s_count]['id'] = $this->s_count;
                    $this->s[$this->s_count]['name'] = $name;
                    $this->s[$this->s_count]['url'] = $url;
                    $this->log($this->s_count . ' SUBCAT: ' . $name, 'yellow');
                    if ($this->gss) {
                        $subsubcategories = $this->subsubcategories($c_count, $this->s_count);
                        $this->s[$this->s_count]['ss'] = $subsubcategories;
                        if((count($subsubcategories) == 0) && ($this->gssp == 1)) {
                            $this->gsp = 1;
                        }
                    }
                    $p = array();
                    if ($this->gsp) {
                        $products = $this->products($url);
                        $this->s[$this->s_count]['p'] = $products;
                        foreach ($products as $item) {
                            $p[] = $item;
                        }
                    }
                    if($this->ws) {
                        $s = $this->s[$this->s_count];
                        if(array_key_exists('p', $s)) {
                            foreach ($s['p'] as $item) {
                                $p[] = $item;
                            }
                        }
                        foreach($s['ss'] as $ss) {
                            if(array_key_exists('p', $ss)) {
                                foreach ($ss['p'] as $item) {
                                    $p[] = $item;
                                }
                            }
                        }

                        $this->writeCsv($p, $this->c[$this->c_count]['name'] . '-' . $this->s[$this->s_count]['name']);
                    }
                }
                $this->s_count++;
            }
        }
        $this->logV("End subcategories crawling");

        return $this->s;
    }

    protected function subsubcategories($c_count, $s_count) {
        $this->ss = array();
        $this->p = array();
        $this->logV('Start subsubcategories crawling');
        //$c_cat = $this->follow($this->c[$num]['url']);
        $c_cat = $this->follow($this->h . $this->url);
        try {
            $c_subsubcats = $c_cat->filter($this->categories_xpath)->children()->eq($c_count - 1)->filter(
                $this->subcategories_xpath
            )->children()->eq($s_count - 1)->filter($this->subsubcategories_xpath)->children();
        } catch(\Exception $e){
            return $this->ss;
        }
        //$c_subcats = $c_cat->filter('ul')->first()->children();
        if (iterator_count($c_subsubcats)) {
            foreach ($c_subsubcats as $i => $content) {
                if(($this->gss <= $this->ss_count) && (($this->gss + $this->mss - 1) >= $this->ss_count)) {
                    $c_subsubcat = new Crawler($content);
                    $item = $c_subsubcat->filter($this->subsubcategory_item_xpath);
                    $name = trim($item->text());
                    $url = $item->attr('href');
                    $this->ss[$this->ss_count]['id'] = $this->ss_count;
                    $this->ss[$this->ss_count]['name'] = $name;
                    $this->ss[$this->ss_count]['url'] = $url;
                    $this->log($this->ss_count . ' SUBSUBCAT: ' . $name);
                    $p = array();
                    if ($this->gssp) {
                        $products = $this->products($url);
                        foreach($products as $item) {
                            $p[] = $item;
                        }
                        //$this->ss[$this->ss_count]['p'] = $products;
                    }
                    if($this->wss) {
                        $this->writeCsv($p, $this->c[$this->c_count]['name'] . '-' . $this->s[$this->s_count]['name'] . '-' . $this->ss[$this->ss_count]['name']);
                    }
                }
                $this->ss_count++;
            }
        }
        $this->logV("End subcategories crawling");

        return $this->ss;
    }
    protected function products($url) {
        $products = array();
        if($c_products = $this->follow($url)) {
            $continue = false;
            do {
                if (iterator_count($c_products->filter($this->productlist_xpath))) {
                    $c_products_list = $c_products->filter($this->productlist_xpath)->children();
                    if (iterator_count($c_products_list)) {
                        foreach ($c_products_list as $i => $content) {
                            $c_product = new Crawler($content);
                            $link = $c_product->filter('h5 a')->attr('href');

                            //if ($c_product_details = $this->follow($link)) {
                            //    $product = $this->product($c_product_details, $link);
                            //} else {
                                $product = $this->product($c_product, $link, true);
                            //}
                            $product['url'] = $link;
                            $products[$this->p_count] = $product;
                        }
                    }
                }
                if ($c_products->filter('#pagination_next a')->count()) {
                    $next = $c_products->filter('#pagination_next a')->first()->attr('href');
                    if ($c_products = $this->follow($this->h.$next)) {
                        $continue = true;
                    }
                } else {
                    $continue = false;
                }

            } while ($continue);
        }

        return $products;
    }

    protected function product($c_product, $link, $from_list = false) {
        $p = array();
        if($from_list) {
            $p['Title'] = trim($c_product->filter('h5 a')->text());
            $p['Handle'] = $this->sluggify($p['Title']);
            $this->log($this->p_count .' NAME: ' . $p['Title'], 'cyan');

            $p['Body'] = null;
            if (iterator_count($c_product->filter('div[itemprop="description"]'))) {
                $p['Body'] = trim($c_product->filter('div[itemprop="description"]')->text());
                $this->logV('DESC: '.$p['Body']);
            }
            $p['Variant Price'] = null;
            die(var_dump(iterator_count($c_product->filter('span[itemprop="price"]'))));
            if (iterator_count($c_product->filter('span[itemprop="price"]'))) {
                $p['Variant Price'] = $c_product->filter('span[itemprop="price"]')->text();
                $this->logV('PRICE: '.$p['Variant Price']);
            }
            $p['Reference'] = null;
            $p['Image Src'] = null;
            $p['Image Alt Text'] = null;
            $p['Variant Image'] = null;
            $img = null;
            if($c_product->filter('.product-image-container img')->count()) {
                $c_images = $c_product->filter('.product-image-container img');
                foreach ($c_images as $i => $content) {
                    $c_image = new Crawler($content);
                    $img = $c_image->attr('src');
                    $img = str_replace('home','thickbox',$img);
                    $img = str_replace('medium','thickbox',$img);

                    $img_local = explode('/', $img);
                    $img_local = end($img_local);
                    $img_edemy = "http://maste.es/d/azaria/original/" . $img_local;
                    try {
                        file_put_contents(
                            "/var/www/".$this->dh."/www/web/d/azaria/original/".$img_local,
                            file_get_contents($img)
                        );
                    } catch (\Exception $e) {

                    }
                    $p['Image Src'] = $img_edemy;
                    //$p['Image Alt Text'] = $title . ' ' . $size;
                    $p['Image Alt Text'] = '';
                    $p['Variant Image'] = $img_edemy;
                    $this->log('IMG: ' . $img);
                }
            }
        } else {
            $p['Title'] = $c_product->filter('h1[itemprop="name"]')->text();
            $p['Handle'] = $this->sluggify($p['Title']);
            $this->log($this->p_count .' NAME: ' . $p['Title'], 'cyan');

            $p['Body'] = null;
            if(iterator_count($c_product->filter('#short_description_content'))) {
                $p['Body'] = trim($c_product->filter('#short_description_content')->text());
                $this->log('BODY: '.$p['Body']);
            }
            if(iterator_count($c_product->filter('#more_info_sheets'))) {
                $p['Body'] = trim($c_product->filter('#more_info_sheets')->text());
                $this->logV('DESC: '.$p['Body']);
            }

            $p['Variant Price'] = null;
            die(var_dump(iterator_count($c_product->filter('span[itemprop="price"]'))));
            if(iterator_count($c_product->filter('span[itemprop="price"]'))) {
                $p['Variant Price'] = $c_product->filter('span[itemprop="price"]')->text();
                $this->log('PRICE: '.$p['Variant Price']);
            }

            if(iterator_count($c_product->filter('#product_reference span'))) {
                $p['Reference'] = $c_product->filter('#product_reference span')->text();
            } else {
                $p['Reference'] = null;
            }
            $this->logV('REFERENCE: '.$p['Reference']);

            $p['Image Src'] = null;
            $p['Image Alt Text'] = null;
            $p['Variant Image'] = null;
            $img = null;
            if($c_product->filter('#image-block img')->count()) {
                $c_images = $c_product->filter('#image-block img');
                foreach ($c_images as $i => $content) {
                    $c_image = new Crawler($content);
                    $img = $c_image->attr('src');
                    $img = str_replace('home','large',$img);
                    $img = str_replace('medium','large',$img);
                    $img_local = explode('/', $img);
                    $img_local = end($img_local);
                    $img_edemy = "http://maste.es/d/azaria/original/" . $img_local;
                    try {
                        file_put_contents(
                            "/var/www/".$this->dh."/www/web/d/azaria/original/".$img_local,
                            file_get_contents($img)
                        );
                    } catch (\Exception $e) {

                    }
                    $p['Image Src'] = $img_edemy;
                    //$p['Image Alt Text'] = $title . ' ' . $size;
                    $p['Image Alt Text'] = '';
                    $p['Variant Image'] = $img_edemy;

                    $this->log('IMG: ' . $img);
                }
            }
        }

        $p['Vendor'] = 'azaria';
        $p['Type'] = $this->c[$this->c_count]['name'] . '-' . $this->s[$this->s_count]['name'];
        $p['Tags'] = $this->c[$this->c_count]['name'] . '-' . $this->s[$this->s_count]['name'];
        if(array_key_exists('Variant Price',$p)) {
            $p['Published'] = 'TRUE';
        } else {
            $p['Published'] = 'FALSE';
        }
        $p['Option1 Name'] = null;
        $p['Option1 Value'] = null;
        $p['Option1 Value'] = null;
        $p['Option2 Name'] = null;
        $p['Option2 Value'] = null;
        $p['Option3 Name'] = null;
        $p['Option3 Value'] = null;
        $p['Variant SKU'] = $p['Reference'];
        $p['Variant Grams'] = 500;
        $p['Variant Weight Unit'] = 'g';
        $p['Variant Inventory Tracker'] = null; //'shopify';
        $p['Variant Inventory Qty'] = 100;
        $p['Variant Inventory Policy'] = 'continue';
        $p['Variant Fulfillment Service'] = 'manual';
        $p['Variant Compare At Price'] = null;
        $p['Variant Requires Shipping'] = 'TRUE';
        $p['Variant Taxable'] = 'TRUE';
        $p['Variant Barcode'] = null;

        /*
                if(iterator_count($c_product->filter('#attributes .attribute_fieldset'))) {
                    $attributes = $c_product->filter('#attributes .attribute_fieldset');
                    foreach ($attributes as $i => $content) {
                        $c_attribute = new Crawler($content);
                        $attribute_name = $c_attribute->filter('label')->text();
                        $this->log('ATTR NAME: ' . $attribute_name);
                        $variant_suffix = $this->getVariantSuffix($link);

                        $values = $c_attribute->filter('.attribute_select')->children();
                        foreach($values as $i => $content) {
                            $c_value = new Crawler($content);
                            $value = trim($c_value->filter('option')->text());
                            $value = str_replace(' ','_',$value);
                            $this->log($link . '#/' . $variant_suffix . '-' . $value);
                            $c_variant = $this->follow($link . '#/' . $variant_suffix . '-' . $value);
                            $this->log('VARIANT: ' . $c_variant->filter('#our_price_display')->text());
                        }

                    }
                    //$this->logV('PRICE: '.$p['price']);
                }
        */

        $this->p_count++;
        return $p;
    }
    protected function categorias_($crawler, $xpath) {
        $c_cats = $crawler->filter($xpath);
        if (iterator_count($c_cats)) {
            foreach ($c_cats as $i => $content) {
                if(
                    ($this->firstCategory <= $this->category_count) &&
                    (($this->firstCategory + $this->maxCategories - 1) >= $this->category_count)
                ) {
                    $c_cat = new Crawler($content);
                    $this->categoria = $c_cat->filter('a')->text();
                    $this->output->writeln('<info>' . 'CAT: ' . $this->categoria . '</info>');

                    $c_subcats = $c_cat->filter('ul')->first()->children();
                    if (iterator_count($c_subcats)) {
                        foreach ($c_subcats as $i => $content) {
                            if(
                                ($this->firstSubcategory <= $this->subcategory_count) &&
                                (($this->firstSubcategory + $this->maxSubcategories - 1) >= $this->subcategory_count)
                            ) {
                                $c_subcat = new Crawler($content);
                                $this->subcategoria = $c_subcat->filter('a')->text();
                                $this->output->writeln('<info>'.'SUBCAT: '.$this->subcategoria.'</info>');

                                if (iterator_count($c_subcat->filter('ul')->first())) {
                                    $c_subsubcats = $c_subcat->filter('ul')->first()->children();
                                } else {
                                    $c_subsubcats = $c_subcats;
                                }
                                if (iterator_count($c_subsubcats)) {
                                    foreach ($c_subsubcats as $i => $content) {
                                        if(
                                            ($this->firstSubsubcategory <= $this->subsubcategory_count) &&
                                            (($this->firstSubsubcategory + $this->maxSubsubcategories - 1) >= $this->subsubcategory_count)
                                        ) {
                                            $c_subsubcat = new Crawler($content);
                                            $this->subsubcategoria = $c_subsubcat->filter('a')->text();
                                            $this->output->writeln(
                                                '<info>'.'SUBSUBCAT: '.$this->subsubcategoria.'</info>'
                                            );

                                            //follow
                                            $link = $c_subsubcat->filter('a')->attr('href');
                                            try {
                                                $c_products = $this->follow($link);
                                            } catch (\Exception $e) {

                                            }

                                            //while next get the products
                                            $continue = false;
                                            do {
                                                if (iterator_count($c_products->filter('.product_list'))) {
                                                    $c_products_list = $c_products->filter(
                                                        '.product_list'
                                                    )->children();
                                                    if (iterator_count($c_products_list)) {
                                                        foreach ($c_products_list as $i => $content) {
                                                            $c_product = new Crawler($content);
                                                            $link = $c_product->filter('.product-name')->attr('href');
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
                                                    $next = $c_products->filter('#pagination_next a')->first(
                                                    )->attr(
                                                        'href'
                                                    );
                                                    //die(var_dump($this->domain));
                                                    $c_products = $this->follow($this->domain.$next);
                                                    $continue = true;
                                                } else {
                                                    $continue = false;
                                                }

                                            } while ($continue);
                                        }
                                        $this->subsubcategory_count++;
                                    }
                                }
                            }
                            if ($this->writeSubcategories) {
                                $this->writeCsv($this->products, $this->subcategoria);
                                empty($this->products);
                            }

                            $this->subcategory_count++;
                        }
                    }
                }
                $this->category_count++;
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

    protected function product_($c_product) {
        $title = $c_product->filter('h1[itemprop="name"]')->text();
        $this->output->writeln('<question>'. $this->subcount++ .' NAME: '.$title.'</question>');

        //$description = $c_product->filter('#short_description_content')->text();
        //$this->output->writeln('DESC: '.$description);

        //$price = $c_product->filter('#our_price_display')->text();
        //$this->output->writeln('PRICE: '.$price);
/*
        $img = null;
        if($c_product->filter('#thumbs_list ul li a')->count()) {
            $c_images = $c_product->filter('#thumbs_list ul li a');
            foreach ($c_images as $i => $content) {
                $c_image = new Crawler($content);
                $img = $c_image->attr('href');
                $this->output->writeln('IMG: ' . $img);
            }
        }
        $this->count++;
        $this->subcount++;
        //$pvp = $this->getPvp($number);
        $pvp = $price;
        $this->products[$this->count]['Handle'] = $this->sluggify($title);
        $this->products[$this->count]['Title'] = $title;
        $this->products[$this->count]['Body'] = $description;
        $this->products[$this->count]['Vendor'] = 'azaria';
        $this->products[$this->count]['Type'] = $this->categoria . ' - ' . $this->subcategoria;
        $this->products[$this->count]['Tags'] = $this->categoria . ' - ' . $this->subcategoria . ",";
        if($pvp) {
            $this->products[$this->count]['Published'] = 'TRUE';
        } else {
            $this->products[$this->count]['Published'] = 'FALSE';
        }
        $this->products[$this->count]['Option1 Name'] = 'Tamaño';
        //$this->products[$this->count]['Option1 Value'] = trim($size . ' ' . $comment);
        $this->products[$this->count]['Option1 Value'] = 0;
        $this->products[$this->count]['Option2 Name'] = null;
        $this->products[$this->count]['Option2 Value'] = null;
        $this->products[$this->count]['Option3 Name'] = null;
        $this->products[$this->count]['Option3 Value'] = null;
        $this->products[$this->count]['Variant SKU'] = null;
        //$this->products[$this->count]['Variant Grams'] = $this->getPeso($number, $size, 'peso');
        $this->products[$this->count]['Variant Grams'] = 0;
        $this->products[$this->count]['Variant Inventory Tracker'] = null; //'shopify';
        $this->products[$this->count]['Variant Inventory Qty'] = 100;
        $this->products[$this->count]['Variant Inventory Policy'] = 'continue';
        $this->products[$this->count]['Variant Fulfillment Service'] = 'manual';
        if($pvp) {
            $this->products[$this->count]['Variant Price'] = $pvp;
        } else {
            $this->products[$this->count]['Variant Price'] = 0;
        }
        $this->products[$this->count]['Variant Compare At Price'] = null;
        $this->products[$this->count]['Variant Requires Shipping'] = 'TRUE';
        $this->products[$this->count]['Variant Taxable'] = 'TRUE';
        //$this->products[$this->count]['Variant Barcode'] = null;
        if($img) {
            $img_local = explode('/', $img);
            $img_local = end($img_local);
            $img_edemy = "http://edemy.es/d/azaria/resized/" . $img_local;
            //die(var_dump($img_local));
            //die(var_dump($img_local));

            file_put_contents("/var/www/" . $this->dh . "/www/web/d/azaria/original/" . $img_local, file_get_contents($img));
            $this->products[$this->count]['Image Src'] = $img_edemy;
            //$this->products[$this->count]['Image Alt Text'] = $title . ' ' . $size;
            $this->products[$this->count]['Image Alt Text'] = '';
            $this->products[$this->count]['Variant Image'] = $img_edemy;
        } else {
            $this->products[$this->count]['Image Src'] = null;
            $this->products[$this->count]['Image Alt Text'] = null;
            $this->products[$this->count]['Variant Image'] = null;
        }
        //$this->products[$this->count]['Variant Weight Unit'] = $this->getPeso($number, $size, 'unidad');
        $this->products[$this->count]['Variant Weight Unit'] = 0;



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
        try {
            //$body = $this->client->get($link)->getBody();
            //$content = $this->getContents($body);

            $content = $this->processAction('info@maste.es', '123456', "http://" . $link);
            $content = $this->getContents($content);
            if($content) {
                $d = new \DOMDocument;
                libxml_use_internal_errors(true);
                $d->loadHTML($content);
                libxml_clear_errors();
                //die('b');
                //die(var_dump($content));
                $content = $d->getElementsByTagName('body')->item(0);
                //die(var_dump($body));
            }
            //die('a');
            $crawler = new Crawler($content);

            return $crawler;
        } catch (\Exception $e) {
            //die(var_dump($e));
            return false;
        }
    }

    public function processAction($user, $passwd, $link) {
        //$proc = $this->getParam($param, null, null, null, true);
        $process = new Process('phantomjs /var/www/maste.es/www/web/d/azaria.js ' . $user . ' ' . $passwd . ' ' . $link);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
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
        $fp = fopen("/var/www/" . $this->dh . "/www/web/d/azaria/csv/" . $file . '.csv', 'w');
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
            if(array_key_exists('Variant Price',$product)) {
                if ($product['Variant Price'] != null) {
                    if ($i == 1) {
                        $product_prev = $product;
                        if (($product['Variant Price'] != null) and ($product['Title'] != null)) {
                            fputcsv(
                                $fp,
                                array(
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
                                ),
                                ','
                            );
                        }
                    } else {

                        //$this->output->writeln($products[$i]['Title']);
                        //$this->output->writeln($products[$i - 1]['Title']);
                        if (($product['Variant Price'] != null) and ($product['Title'] != null)) {
                            if ($product['Title'] == $product_prev['Title']) {
                                fputcsv(
                                    $fp,
                                    array(
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
                                    ),
                                    ','
                                );
                            } else {
                                fputcsv(
                                    $fp,
                                    array(
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
                                    ),
                                    ','
                                );
                            }
                        }
                        $product_prev = $product;
                    }
                    $i++;
                }
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

    protected function log($msg, $fg = null, $bg = null, $options = array()) {
        $style = new OutputFormatterStyle($fg, $bg, $options);
        $this->output->getFormatter()->setStyle('custom', $style);
        $this->output->writeln('<custom>' . $msg . '</>');
    }

    protected function logV($msg, $fg = null, $bg = null, $options = array()) {
        if ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $this->log($msg, $fg, $bg, $options);
        }
    }
}
