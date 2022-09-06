<?php

class WPAutoloader
{
    /**
     * log any bad registrations
     * @var array $bad_registrations
     */
    protected array $registrations = [];

    /**
     * track plugin name, namespace, and root collisions
     * @var array $collisions
     */
    protected array $collisions = [];

    /**
     * @return void
     */
    public static function init(): void
    {
        add_action('plugins_loaded', [new static, 'processRegistrations'], 10000, 0);
    }

    /**
     * Register namespaces and autoloader
     * @return void
     */
    public static function processRegistrations(): void
    {
        do_action('wpal_autoloader_register', $this)
        spl_autoload_register([$this, 'autoload']);
        do_action('wpal_loaded', $this);
    }
    
    public function add($namespace, $settings) 
    {
        if($this->registrations[$namespace] ?? null) {
            $this->collisions[$namespace] = $settings;
        } else {
            $this->registrations[$namespace] = $settings;
        }
    }

    /**
     * Autoload supported modules
     * @param string $class
     * @return void
     */
    public function autoload(string $class): void
    {        
        [$namespace, $module] = array_pad(explode('\\', $class), 2, '');
        
        if($reg = $this->registrations[$namespace]) 
        {
            if($reg['mappings'][$module] ?? null) {
                require $file = $reg['mappings'][$module];
            }
        
            $folder = $reg['folder'];

            $file = rtrim($folder, '\\/') . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($namespace)) . '.php';
            if (is_file($file)) {
                require $file;
                return;
            }
        }
        
        return;
    }
}

WPAutoloader::init();
