<?php

class TestPipelineBuilder
{
    /**
     * @param $jobKey
     * @param $image
     * @param $tag
     * @return string
     */
    public function buildJob($jobKey, $image, $tag)
    {
        if ($image !== 'play' && $image !== 'dev') {
            return '';
        }

        $imageFull = 'dockware/'.$image.':'.$tag;
        $isDev = ($image === 'dev');

        $defaultPHP = '8';
        $php81 = true;
        $php8 = true;
        $php74 = true;
        $php73 = true;
        $php72 = true;
        $php71 = true;
        $php7 = true;
        $php56 = true;
        $sodium = true;

        $node = '12';
        $composer = '2';

        if ($tag === 'latest' || version_compare($tag, '6.0') >= 0) {
            # SHOPWARE 6
            $php73 = false;
            $php72 = false;
            $php71 = false;
            $php7 = false;
            $php56 = false;
        } else {
            if (version_compare($tag, '6.0') >= 0) {
                # SHOPWARE 6
                $php56 = false;
            } else {
                if (version_compare($tag, '5.7') >= 0) {
                    # SHOPWARE >= 5.7
                    $defaultPHP = '7';

                    $php72 = true;
                    $php71 = false;
                    $php7 = false;
                    $php56 = false;
                } else {
                    # SHOPWARE < 5.7
                    $composer = '1';

                    $defaultPHP = '5';
                    $php81 = false;
                    $php8 = false;
                    $php74 = false;
                    $php73 = false;
                    $php72 = false;
                    $php71 = false;
                    $php7 = true;
                    $php56 = true;
                    $sodium = false;
                }
            }
        }

        $xml = '<svrunit setupTime="30">
    <testsuites>
    ';

        $xml .= '
        <testsuite name="'.$imageFull.', Command Runner" dockerImage="'.$imageFull.'" dockerCommandRunner="true">'.PHP_EOL;
        if ($isDev) {
            $xml .= '            <directory>./../../tests/shared/command-runner/dev</directory>'.PHP_EOL;
        } else {
            $xml .= '            <directory>./../../tests/shared/command-runner/play</directory>'.PHP_EOL;
        }
        $xml .= '        </testsuite>
        ';

        # -------------------------------------------------------------------------------------------------------------------------------

        $devPart = '
            <directory>./../../tests/images/dev</directory>
            <directory>./../../tests/shared/dev</directory>
            <directory>./../../tests/packages/node/v'.$node.'</directory>
            <directory>./../../tests/packages/composer/v'.$composer.'</directory>';
        if (!$isDev) {
            $devPart = '';
        }

        $xml .= '
        <testsuite name="'.$imageFull.', Basic Checks" dockerImage="'.$imageFull.'">
            <directory>./../../tests/shared/base</directory>'.$devPart.'
            <directory>./../../tests/packages/php/php'.$defaultPHP.'</directory>
        </testsuite>
         ';

        # -------------------------------------------------------------------------------------------------------------------------------

        if ($isDev) {
            $xml .= '
        <testsuite name="'.$imageFull.', ENV Node Version Switch" dockerImage="'.$imageFull.'" dockerEnv="NODE_VERSION=16">
            <directory>./../../tests/packages/node/v16</directory>
        </testsuite>
            ';
        }

        # -------------------------------------------------------------------------------------------------------------------------------
        $testXdebugOff = true;
        if ($php81) {
            $xdebug = ($isDev) ? '3' : '';
            $xml .= $this->buildVersion($imageFull, '8.1', '8.1', $xdebug, $sodium, $testXdebugOff);
            $testXdebugOff = false;
        }

        # -------------------------------------------------------------------------------------------------------------------------------

        if ($php8) {
            $xdebug = ($isDev) ? '3' : '';
            $xml .= $this->buildVersion($imageFull, '8.0', '8', $xdebug, $sodium, $testXdebugOff);
            $testXdebugOff = false;
        }

        # -------------------------------------------------------------------------------------------------------------------------------

        if ($php74) {
            $xdebug = ($isDev) ? '3' : '';
            $xml .= $this->buildVersion($imageFull, '7.4', '7', $xdebug, $sodium, $testXdebugOff);
            $testXdebugOff = false;
        }

        # -------------------------------------------------------------------------------------------------------------------------------

        if ($php73) {
            $xdebug = ($isDev) ? '3' : '';
            $xml .= $this->buildVersion($imageFull, '7.3', '7', $xdebug, $sodium, $testXdebugOff);
            $testXdebugOff = false;
        }

        # -------------------------------------------------------------------------------------------------------------------------------

        if ($php72) {
            $xdebug = ($isDev) ? '3' : '';
            $xml .= $this->buildVersion($imageFull, '7.2', '7', $xdebug, $sodium, $testXdebugOff);
            $testXdebugOff = false;
        }

        # -------------------------------------------------------------------------------------------------------------------------------

        if ($php71) {
            $xdebug = ($isDev) ? '2' : '';
            $xml .= $this->buildVersion($imageFull, '7.1', '7', $xdebug, $sodium, $testXdebugOff);
            $testXdebugOff = false;
        }

        # -------------------------------------------------------------------------------------------------------------------------------

        if ($php7) {
            $xdebug = ($isDev) ? '2' : '';
            $xml .= $this->buildVersion($imageFull, '7.0', '7', $xdebug, $sodium, $testXdebugOff);
            $testXdebugOff = false;
        }

        # -------------------------------------------------------------------------------------------------------------------------------

        if ($php56) {
            $xdebug = ($isDev) ? '2' : '';
            $xml .= $this->buildVersion($imageFull, '5.6', '5', $xdebug, $sodium, $testXdebugOff);
        }

        # -------------------------------------------------------------------------------------------------------------------------------

        $xml .= '
    </testsuites >
</svrunit >
    ';

        return $xml;
    }

    /**
     * @param $imageFull
     * @param $php
     * @param $xDebug
     * @return string
     */
    private function buildVersion($imageFull, $fullPHP, $php, $xDebug, $sodium, bool $testXdebugOff)
    {
        $xml = PHP_EOL;
        $xml .= '        <testsuite name="'.$imageFull.', PHP '.$fullPHP.', XDebug ON" dockerImage="'.$imageFull.'" dockerEnv="PHP_VERSION='.$fullPHP.',XDEBUG_ENABLED=1">'.PHP_EOL;
        $xml .= '            <directory>./../../tests/packages/php/php'.$php.'</directory>'.PHP_EOL;

        if (!empty($xDebug)) {
            $xml .= '            <directory>./../../tests/packages/xdebug/xdebug'.$xDebug.'</directory>'.PHP_EOL;
        }

        if ($sodium) {
            $xml .= '            <directory>./../../tests/packages/sodium</directory>'.PHP_EOL;
        }

        $xml .= '        </testsuite>'.PHP_EOL;

        if ($testXdebugOff) {
            //disabled xdebug
            $xml .= '        <testsuite name="'.$imageFull.', PHP '.$fullPHP.', XDebug OFF" dockerImage="'.$imageFull.'" dockerEnv="PHP_VERSION='.$fullPHP.',XDEBUG_ENABLED=0">'.PHP_EOL;
            $xml .= '            <directory>./../../tests/packages/xdebug/xdebug-off</directory>'.PHP_EOL;

            $xml .= '        </testsuite>'.PHP_EOL;
        }

        return $xml;
    }
}