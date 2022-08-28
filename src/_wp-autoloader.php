<?php

class WPAutoloader
{
    /**
     * Namespaces registered by other plugins
     * @var array $namespaces
     */
    public static array $namespaces = [];

    /**
     * @return void
     */
    public static function init(): void
    {
        add_action('plugins_loaded', [self::class, 'processRegistrations'], 10000, 0);
    }

    /**
     * Register namespaces and autoloader
     * @return void
     */
    public static function processRegistrations(): void
    {
        foreach(apply_filters('wp_autoloader_register', []) as $registration) {
            foreach($registration['mappings'] as $prefix => $path) {
                self::$namespaces[$prefix] = $path;
            }
        }

        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload supported modules
     * @param string $class
     * @return void
     */
    public static function autoload(string $module): void
    {
        // Build list of possible prefixes, based on namespace path
        $possible_prefixes = [];
        for($i = 0, $chunks = explode('\\', $module); $i < count($chunks); $i++) {
            if($i === 0) {
                $possible_prefixes[] = $chunks[$i] . '\\';
                continue;
            }

            $possible_prefixes[] = $possible_prefixes[$i - 1] . $chunks[$i] . '\\';
        }
        $possible_prefixes = array_reverse($possible_prefixes);

        // Determine if a class prefix is supported, checking
        // for longest possible prefix first
        $prefix = null;
        foreach($possible_prefixes as $test) {
            if(array_key_exists($test, self::$namespaces)) {
                $prefix = $test;
                break;
            }
        }

        if (!$prefix) return;

        // Find and load file
        $baseDir = self::$namespaces[$prefix];
        $path = str_replace('\\', DIRECTORY_SEPARATOR, substr($module, strlen($prefix)));

        require "{$baseDir}{$path}.php";
    }
}

WPAutoloader::init();