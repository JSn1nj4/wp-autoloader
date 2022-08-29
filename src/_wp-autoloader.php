<?php

class WPAutoloader
{
    /**
     * log any bad registrations
     * @var array $bad_registrations
     */
    public static array $bad_registrations = [];

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
            if (self::registrationMalformed($registration)) continue;

            foreach($registration['mappings'] as $prefix => $path) {
                self::$namespaces[$prefix] = $path;
            }
        }

        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload supported modules
     * @param string $module
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

    /**
     * Ensure a given registration payload is structured correctly
     *
     * If a registration payload is malformed, the issue will be logged along with the relevant information - the entire payload or the parts that had the issue.
     *
     * Types of issues checked for:
     * - the whole payload: not an array
     * - name field: missing, or not a string
     * - mappings: missing, not an array
     * - namespaces: not a string, pattern mismatch
     * - paths: not a string, path not found
     *
     * Payload example 1
     * ```
     * [
     *   'name' => 'registration name',
     *   'maps' => [
     *     'PluginBaseNamespace\\' => '/absolute/path/to/base/dir',
     *   ]
     * ]
     * ```
     *
     * Payload example 2
     * ```
     * [
     *   'name' => 'registration name',
     *   'maps' => [
     *     'PluginBaseNamespace\\SubNamespace' => '/absolute/path/to/base/dir',
     *     'PluginBaseNamespace\\SubNamespace2' => '/absolute/path/to/base/dir2',
     *     'PluginBaseNamespace\\SubNamespace3' => '/absolute/path/to/base/dir3',
     *   ]
     * ]
     * ```
     * @param mixed $registration
     * @return bool
     */
    protected static function registrationMalformed(mixed $registration): bool
    {
        if (!is_array($registration)) {
            self::$bad_registrations[] = [
                'type' => 'not_array',
                'item' => 'registration',
                'payload' => $registration,
            ];

            return true;
        }

        if (!isset($registration['name'])) {
            self::$bad_registrations[] = [
                'type' => 'missing_field',
                'item' => 'name',
                'payload' => $registration,
            ];

            return true;
        }

        if (!is_string($registration['name'])) {
            self::$bad_registrations[] = [
                'type' => 'bad_field_type',
                'item' => 'name',
                'payload' => $registration,
            ];

            return true;
        }

        if (!isset($registration['mappings'])) {
            self::$bad_registrations[] = [
                'type' => 'missing_field',
                'item' => 'mappings',
                'payload' => $registration,
            ];

            return true;
        }

        if (!is_array($registration['mappings'])) {
            self::$bad_registrations[] = [
                'type' => 'bad_field_type',
                'item' => 'mappings',
                'payload' => $registration,
            ];

            return true;
        }

        foreach($registration['mappings'] as $namespace => $path) {
            // Make sure `$namespace` key is a string, not an int
            if (!is_string($namespace)) {
                self::$bad_registrations[] = [
                    'type' => 'bad_field_type',
                    'item' => 'namespace',
                    'payload' => [
                        'name' => $registration['name'],
                        'mappings' => [$namespace => $path],
                    ],
                ];

                return true;
            }

            // Check that namespace pattern is correct
            if (!preg_match('/^(?:[a-z]+[\da-z_]*\\\)+$/i', $namespace)) {
                self::$bad_registrations[] = [
                    'type' => 'pattern_mismatch',
                    'item' => 'namespace',
                    'payload' => [
                        'name' => $registration['name'],
                        'mappings' => [$namespace => $path],
                    ],
                ];

                return true;
            }

            if (!is_string($path)) {
                self::$bad_registrations[] = [
                    'type' => 'bad_field_type',
                    'item' => 'path',
                    'payload' => [
                        'name' => $registration['name'],
                        'mappings' => [$namespace => $path],
                    ],
                ];

                return true;
            }

            if (!is_dir($path)) {
                self::$bad_registrations[] = [
                    'type' => 'path_not_found',
                    'item' => 'path',
                    'payload' => [
                        'name' => $registration['name'],
                        'mappings' => [$namespace => $path],
                    ],
                ];

                return true;
            }
        }

        return false;
    }
}

WPAutoloader::init();