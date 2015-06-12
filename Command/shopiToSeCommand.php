<?php

namespace eDemy\ProductBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class shopiToSeCommand extends Command
{
    public $contrareembolso;
    public $row;
    public $cr;
    public $pp;
    public $desdedia;
    public $hastadia;
    public $desdehora;
    public $hastahora;
    public $data;
    public $output;
    
    private $csvParsingOptions = array(
        'finder_in' => '.',
        'finder_name' => '*.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setName('edemy:shopi:se')
            ->setDescription('')
            //->addArgument('file', InputArgument::REQUIRED, 'Fichero a convertir')
            //->addOption('desdedia', null, InputOption::VALUE_REQUIRED, 'desde dia')
            //->addOption('hastadia', null, InputOption::VALUE_REQUIRED, 'hasta dia')
            //->addOption('desdehora', null, InputOption::VALUE_REQUIRED, 'desde hora')
            //->addOption('hastahora', null, InputOption::VALUE_REQUIRED, 'hasta hora')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->contrareembolso = "Contrareembolso (+1.45€)";
        $this->transferencia = "Transferencia o ingreso en cuenta";
        $this->paypal = "PayPal Payments Standard";
        $this->row = array();
        $this->cr = array();
        $this->pp = array();
        $this->output = $output;
        //$file = $input->getArgument('file');
        //$this->desdedia = $input->getOption('desdedia');
        //$this->hastadia = $input->getOption('hastadia');
        //$this->desdehora = $input->getOption('desdehora');
        //$this->hastahora = $input->getOption('hastahora');
        
        /*
        $dialog = $this->getHelperSet()->get('dialog');
        $this->desdedia = $dialog->ask(
            $output,
            'Desde dia',
            ''
        );
        $this->hastadia = $dialog->ask(
            $output,
            'Hasta dia',
            ''
        );
        $this->desdehora = $dialog->ask(
            $output,
            'Desde hora',
            ''
        );
        $this->hastahora = $dialog->ask(
            $output,
            'Hasta hora',
            ''
        );
        */
        $csv = $this->parseCSV();
        $output->writeln('Finalizado');
    }
    
    private function parseCSV() 
    {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];
        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($this->csvParsingOptions['finder_name'])
        ;
        foreach ($finder as $file) { $csv = $file; }
        
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            $this->row[0]['pedido'] = 'pedido';
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                if ($ignoreFirstLine && $i == 0) { $i++; continue; }
                $this->getRow($data, $i);
                $i++;
            }
            fclose($handle);
            
            $fp = fopen('seur.csv', 'w');
            foreach ($this->pp as $linea) {
                if($this->filtro($linea)) {
                    fputcsv($fp, array(
                        '11414',
                        $linea['pedido'],
                        $linea['referencia'],
                        $linea['telefono'],
                        $linea['nombre'],
                        str_replace(';', '', $linea['direccion']),
                        str_replace('"', '', (str_replace('=', '', $linea['cp']))),
                        $linea['ciudad'],
                        $linea['cantidad'],
                    ),';',' ');
                }
            }
            //fclose($fp);
            //$fp = fopen('contrareembolso.csv', 'w');
            foreach ($this->cr as $linea) {
                if($this->filtro($linea)) {
                    fputcsv($fp, array(
                        '70492',
                        $linea['pedido'],
                        $linea['referencia'],
                        $linea['telefono'],
                        $linea['nombre'],
                        str_replace(';', '', $linea['direccion']),
                        str_replace('"', '', (str_replace('=', '', $linea['cp']))),
                        $linea['ciudad'],
                        $linea['cantidad'],
                        $linea['total'],
                    ),';',' ');
                }
            }
            fclose($fp);
        }

        return true;
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

    private function getRow($data, $i) {
        $this->row[$i]['pedido'] = $data[0];
        $this->row[$i]['email'] = $data[1];

        if($data[2] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['estado'] = $this->row[$i-1]['estado'];
        } else {
            $this->row[$i]['estado'] = $data[2];
        }

        if($data[3] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['fecha_pago'] = $this->row[$i-1]['fecha_pago'];
        } else {
            $this->row[$i]['fecha_pago'] = $data[3];
        }
        
        if($data[4] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['fullfillment'] = $this->row[$i-1]['fullfillment'];
        } else {
            $this->row[$i]['fullfillment'] = $data[4];
        }
        if($data[5] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['fecha fullfillment'] = $this->row[$i-1]['fecha fullfillment'];
        } else {
            $this->row[$i]['fecha fullfillment'] = $data[5];
        }
        if($data[6] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['marketing'] = $this->row[$i-1]['marketing'];
        } else {
            $this->row[$i]['marketing'] = $data[6];
        }
        if($data[7] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['moneda'] = $this->row[$i-1]['moneda'];
        } else {
            $this->row[$i]['moneda'] = $data[7];
        }
        if($data[8] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['subtotal'] = $this->row[$i-1]['subtotal'];
        } else {
            $this->row[$i]['subtotal'] = $data[8];
        }

        if($data[48] == $this->contrareembolso) {
            if($data[9] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
                $this->row[$i]['envio'] = $this->row[$i-1]['envio'];
            } else {
                $this->row[$i]['envio'] = (string) (((float) $data[9]) + 1.45);
            }
            if($data[10] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
                $this->row[$i]['impuestos'] = $this->row[$i-1]['impuestos'];
            } else {
                $this->row[$i]['impuestos'] = $data[10];
            }
            if($data[11] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
                $this->row[$i]['total'] = $this->row[$i-1]['total'];
            } else {
                $this->row[$i]['total'] = (string) (((float) $data[11]) + 1.45);
            }
        } else {
            if($data[9] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
                $this->row[$i]['envio'] = $this->row[$i-1]['envio'];
            } else {
                $this->row[$i]['envio'] = $data[9];
            }
            if($data[10] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
                $this->row[$i]['impuestos'] = $this->row[$i-1]['impuestos'];
            } else {
                $this->row[$i]['impuestos'] = $data[10];
            }
            if($data[11] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
                $this->row[$i]['total'] = $this->row[$i-1]['total'];
            } else {
                $this->row[$i]['total'] = $data[11];
            }
        }
        if($data[12] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['codigo descuento'] = $this->row[$i-1]['codigo descuento'];
        } else {
            $this->row[$i]['codigo descuento'] = $data[12];
        }
        if($data[13] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['descuento'] = $this->row[$i-1]['descuento'];
        } else {
            $this->row[$i]['descuento'] = $data[13];
        }
        if($data[14] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['metodo envio'] = $this->row[$i-1]['metodo envio'];
        } else {
            $this->row[$i]['metodo envio'] = $data[14];
        }

        if($data[15] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['fecha creacion'] = $this->row[$i-1]['fecha creacion'];
        } else {
            $this->row[$i]['fecha creacion'] = $data[15];
        }
        $this->row[$i]['cantidad'] = $data[16];
        $this->row[$i]['nombre'] = $data[17];
        $this->row[$i]['precio'] = $data[18];
        $this->row[$i]['precio comparado'] = $data[19];
        $this->row[$i]['referencia'] = $data[20];
        $this->row[$i]['requiere envio'] = $data[21];
        $this->row[$i]['con impuestos'] = $data[22];
        $this->row[$i]['estado fullfilment'] = $data[23];
        
        if($data[24] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_nombre'] = $this->row[$i-1]['facturacion_nombre'];
        } else {
            $this->row[$i]['facturacion_nombre'] = $data[24];
        }
        
        if($data[25] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_direccion'] = $this->row[$i-1]['facturacion_direccion'];
        } else {
            $this->row[$i]['facturacion_direccion'] = $data[25];
        }
        
        if($data[26] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_direccion1'] = $this->row[$i-1]['facturacion_direccion1'];
        } else {
            $this->row[$i]['facturacion_direccion1'] = $data[26];
        }
        
        if($data[27] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_direccion2'] = $this->row[$i-1]['facturacion_direccion2'];
        } else {
            $this->row[$i]['facturacion_direccion2'] = $data[27];
        }
        
        if($data[28] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_empresa'] = $this->row[$i-1]['facturacion_empresa'];
        } else {
            $this->row[$i]['facturacion_empresa'] = $data[28];
        }
        
        if($data[29] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_ciudad'] = $this->row[$i-1]['facturacion_ciudad'];
        } else {
            $this->row[$i]['facturacion_ciudad'] = $data[29];
        }
        
        if($data[30] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_cp'] = $this->row[$i-1]['facturacion_cp'];
        } else {
            $this->row[$i]['facturacion_cp'] = $data[30];
        }
        
        if($data[31] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_provincia'] = $this->row[$i-1]['facturacion_provincia'];
        } else {
            $this->row[$i]['facturacion_provincia'] = $data[31];
        }
        
        if($data[32] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_pais'] = $this->row[$i-1]['facturacion_pais'];
        } else {
            $this->row[$i]['facturacion_pais'] = $data[32];
        }
        
        if($data[33] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['facturacion_telefono'] = $this->row[$i-1]['facturacion_telefono'];
        } else {
            $this->row[$i]['facturacion_telefono'] = $data[33];
        }
        
        if($data[34] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['nombre'] = $this->row[$i-1]['nombre'];
        } else {
            $this->row[$i]['nombre'] = $data[34];
        }
        
        if($data[35] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['direccion'] = $this->row[$i-1]['direccion'];
        } else {
            $this->row[$i]['direccion'] = $data[35];
        }
        
        if($data[36] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['direccion1'] = $this->row[$i-1]['direccion1'];
        } else {
            $this->row[$i]['direccion1'] = $data[36];
        }
        
        if($data[37] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['direccion2'] = $this->row[$i-1]['direccion2'];
        } else {
            $this->row[$i]['direccion2'] = $data[37];
        }
        
        if($data[38] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['empresa'] = $this->row[$i-1]['empresa'];
        } else {
            $this->row[$i]['empresa'] = $data[38];
        }
        
        if($data[39] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['ciudad'] = $this->row[$i-1]['ciudad'];
        } else {
            $this->row[$i]['ciudad'] = $data[39];
        }
        
        if($data[40] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['cp'] = $this->row[$i-1]['cp'];
        } else {
            $this->row[$i]['cp'] = $data[40];
        }
        
        if($data[41] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['provincia'] = $this->row[$i-1]['provincia'];
        } else {
            $this->row[$i]['provincia'] = $data[41];
        }
        
        if($data[42] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['pais'] = $this->row[$i-1]['pais'];
        } else {
            $this->row[$i]['pais'] = $data[42];
        }
        
        if($data[43] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['telefono'] = $this->row[$i-1]['telefono'];
        } else {
            $this->row[$i]['telefono'] = $data[43];
        }
        
        if($data[44] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['notas'] = $this->row[$i-1]['notas'];
        } else {
            $this->row[$i]['notas'] = $data[44];
        }
        
        if($data[45] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['atributos'] = $this->row[$i-1]['atributos'];
        } else {
            $this->row[$i]['atributos'] = $data[45];
        }
        
        if($data[46] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['iva'] = $this->row[$i-1]['iva'];
        } else {
            $this->row[$i]['iva'] = $data[46];
        }
        
        if($data[47] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['fecha cancelación'] = $this->row[$i-1]['fecha cancelación'];
        } else {
            $this->row[$i]['fecha cancelación'] = $data[47];
        }
        
        if($data[48] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['metodo pago'] = $this->row[$i-1]['metodo pago'];
        } else {
            $this->row[$i]['metodo pago'] = $data[48];
        }
        
        if($data[49] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['referencia pago'] = $this->row[$i-1]['referencia pago'];
        } else {
            $this->row[$i]['referencia pago'] = $data[49];
        }
        
        if($data[50] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['importe devuelto'] = $this->row[$i-1]['importe devuelto'];
        } else {
            $this->row[$i]['importe devuelto'] = $data[50];
        }
        
        $this->row[$i]['vendedor'] = $data[51];
        
        if($data[52] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['id'] = $this->row[$i-1]['id'];
        } else {
            $this->row[$i]['id'] = $data[52];
        }
        
        if($data[53] == "" and ($this->row[$i]['pedido'] == $this->row[$i-1]['pedido'])) {
            $this->row[$i]['etiquetas'] = $this->row[$i-1]['etiquetas'];
        } else {
            $this->row[$i]['etiquetas'] = $data[53];
        }
        
        //$fecha = new \DateTime($this->row[$i]['fecha creacion']);
        //$d = (int) $f->format('d');
        
        switch($this->row[$i]['metodo pago']) {
            case $this->contrareembolso:
                $this->cr[] = $this->row[$i];
                break;
            case $this->paypal:
                $this->pp[] = $this->row[$i];
                break;
        }
    }
}
