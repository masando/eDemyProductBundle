<?php

namespace eDemy\ProductBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class eDemyProductBundle extends Bundle
{
    public static function getBundleName($type = null)
    {
        if ($type == null) {

            return 'eDemyProductBundle';
        } else {
            if ($type == 'Simple') {

                return 'Product';
            } else {
                if ($type == 'simple') {

                    return 'product';
                }
            }
        }
    }

    public static function eDemyBundle() {

        return true;
    }
}
