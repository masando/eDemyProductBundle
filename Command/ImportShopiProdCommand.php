<?php

namespace eDemy\ProductBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use eDemy\ProductBundle\Entity\Product;
use eDemy\ProductBundle\Entity\Category;
use eDemy\ProductBundle\Entity\Imagen;

class ImportShopiProdCommand extends ContainerAwareCommand
{
    public $file, $namespace;
    public $input;
    public $output;

    private $csvParsingOptions = array(
        'finder_in' => 'app/data/product/import',
        //'finder_name' => '.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setName('edemy:product:importshopify')
            ->setDescription('Import products from shopify csv')
            ->addArgument('file', InputArgument::REQUIRED, 'Data file with products?')
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL, 'Namespace')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        if($this->input->getArgument('file') != null) {
            $this->file = $this->input->getArgument('file');
            if($this->input->getOption('namespace') != null) {
                $this->namespace = $this->input->getOption('namespace');
            }
            $this->parseCSV();
        }
        $this->output->writeln('Finalizado');
    }

    private function parseCSV()
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];
        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($this->file)
        ;
        
        foreach ($finder as $file) { $csv = $file; }
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                if ($ignoreFirstLine && $i == 0) { $i++; continue; }
                $this->getRow($data, $i);
                $i++;
            }
            fclose($handle);
            //$this->em->flush();
        }

        return true;
    }

    private function getRow($data, $i) {
        $p = $this->em->getRepository('eDemyProductBundle:Product')->findOneBy(array(
            'slug' => $data[0],
            'namespace' => $this->namespace,
        ));
        if($p != null) {
            //if( ($p->getCategory() == null) and ($data[4] != null)) {
                $cat = $this->em->getRepository('eDemyProductBundle:Category')->findOneBy(array(
                    'name' => $data[4],
                    'namespace' => $this->namespace,
                ));
                if($cat == null) {
                    $cat = new Category();
                    $cat->setName($data[4]);
                    $cat->setPublished(1);
                    $cat->setNamespace($this->namespace);
                    $this->output->writeln('categoría creada: ' . $data[4]);
                } else {
                    $this->output->writeln('categoría existente: ' . $data[4]);
                }
                $p->setCategory($cat);
                $p->setPrice($data[19]);
                $p->setPriceUnit('unidad');
                $this->em->persist($cat);
                $this->output->writeln('categoría añadida a producto: ' . $p->getName() . ' - ' . $p->getId());
            //}
            $this->output->writeln('producto actualizado: ' . $p->getName() . ' - ' . $p->getId());
        } else {
            $product = new Product();
            $imagen = new Imagen();
            
            $product->setNamespace($this->namespace);
            
            $product->setSlug($data[0]);
            $product->setName($data[1]);
            $product->setDescription($data[2]);
            //$data[3]; //vendor
            //$data[4]; //type
            $cat = $this->em->getRepository('eDemyProductBundle:Category')->findOneBy(array(
                'name' => $data[4],
                'namespace' => $this->namespace,
            ));
            if($cat == null) {
                $cat = new Category();
                $cat->setName($data[4]);
                $cat->setPublished(1);
                $cat->setNamespace($this->namespace);
                $this->output->writeln('categoría creada: ' . $data[4]);
                $this->em->persist($cat);
            } else {
                $this->output->writeln('categoría existente: ' . $data[4]);
            }
            $product->setCategory($cat);
            //$data[5]; //tags
            //$data[6]; //published
            if($data[6] == "TRUE") {
                $product->setPublished(1);
            }
            //$data[7]; //option1 name
            //$data[8]; //option1 value
            //$data[9]; //option2 name
            //$data[10]; //option2 value
            //$data[11]; //option3 name
            //$data[12]; //option3 value
            //$data[13]; //variant SKU
            //$data[14]; //variant grams
            //$data[15]; //variant inventory tracker
            //$data[16]; //variant inventory qty
            //$data[17]; //variant inventory policy
            //$data[18]; //variant fullfilment service
            //$data[19]; //variant price
            $product->setPrice($data[19]);
            $product->setPriceUnit('unidad');
            //$data[20]; //variant compare at price
            //$data[21]; //variant requires shipping
            //$data[22]; //variant taxable
            //$data[23]; //variant barcode

            //$data[24]; //image src
            //$data[25]; //image alt text
            $img = '/tmp/productimport';
            $fileNotFound = false;
            try {
                file_put_contents($img, file_get_contents($data[24]));
            } catch (\Exception $e) {
                $fileNotFound = true;
            }
            if($fileNotFound == false) {
                $file = new File($img);

                $imagen->setName($data[25]);
                $imagen->setPublished(1);
                $imagen->setFile($file);
                
                $product->addImagen($imagen);

                $this->em->persist($imagen);
                $this->output->writeln('producto creado: ' . $i);
            }

            $this->em->persist($product);
        }
        $this->em->flush();

    }

    public function filtro($linea) {
        return true;
        $f = new \DateTime($linea['fecha creacion']);
        $dia_pedido = (int) $f->format('d');
        $mes_pedido = (int) $f->format('m');
        $año_pedido = (int) $f->format('Y');
        $h_pedido = (int) $f->format('H');

        $desde = \DateTime::createFromFormat('d/m', $this->desdedia);
        $dia_desde = (int) $desde->format('d');
        $mes_desde = (int) $desde->format('m');
        $hasta = \DateTime::createFromFormat('d/m', $this->hastadia);
        $dia_hasta = (int) $hasta->format('d');
        $mes_hasta = (int) $hasta->format('m');

        if($mes_pedido >= $mes_desde and $mes_pedido <= $mes_hasta) {
            if($dia_pedido >= $dia_desde and $dia_pedido <= $dia_hasta) {
                if(($h_pedido >= (int) $this->desdehora) and ($h_pedido <= (int) $this->hastahora)) {
                    return true;
                }
            }
        }
        
        return false;
    }
} 
