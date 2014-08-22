<?php namespace Streams\AddonComposer;

/**
 * Class Generator
 *
 * @package Anomaly\Autoload
 * @author  Osvaldo Brignoni
 */
class Generator
{
    /**
     * The name of the generated file
     *
     * @var string
     */
    protected $filename = 'streams.addon.autoload.php';

    /**
     * The composer filename
     *
     * @var string
     */
    protected $composerJson = 'composer.json';

    /**
     * The CLI confirmation message
     *
     * @var string
     */
    protected $message = 'generated vendor autoload file.';

    /**
     * Initialized array of supported PSR standards
     *
     * @var array
     */
    protected $autoload = [
        'psr-0' => [],
        'psr-4' => [],
    ];

    /**
     * Static generate autoload
     *
     * @param $event
     * @return mixed
     */
    public static function generate($event)
    {
        /** @var $instance Generator */
        $instance = new static;
        return $instance->runGenerate();
    }

    /**
     * Run generate from the Generator instance
     *
     * @return array
     */
    protected function runGenerate()
    {
        $packageName = $this->getPackageName();

        $vendors = dirname(dirname(dirname(dirname(dirname(__DIR__)))));

        foreach ($this->directories($vendors) as $vendor) {

            foreach ($this->directories($vendor) as $package) {

                $composer = $this->getPackageComposerJson($package);

                $packageFolder = basename($vendor) . '/' . basename($package);

                if ($packageFolder != $packageName and is_object($composer)) {

                    foreach ($this->autoload as $psr => $a) {

                        if (property_exists($composer->autoload, $psr)) {

                            $psrArray = (array)$composer->autoload->{$psr};

                            foreach ($psrArray as $namespace => $src) {

                                if (is_array($src)) {
                                    foreach ($src as &$s) {
                                        $s = $packageFolder . '/' . $s;
                                    }
                                } else {
                                    $src = $packageFolder . '/' . $src;
                                }

                                $this->autoload[$psr][$namespace] = $src;
                            }
                        }
                    }
                }
            }
        }

        file_put_contents($vendors . '/' . $this->filename, $this->compileAutoload($this->autoload));

        echo "{$packageName} {$this->message}\n";

        return $this->autoload;
    }

    /**
     * Get this package name
     *
     * @return string
     */
    public function getPackageName()
    {
        $package = dirname(__DIR__);

        $vendor = dirname($package);

        return $vendor.'/'.$package;
    }

    /**
     * Compile autoload string
     *
     * @param array $autoload
     * @return string
     */
    public function compileAutoload(array $autoload)
    {

        $string = "<?php return [\n";

        foreach ($autoload as $psr => $a) {

            if (!empty($autoload[$psr])) {

                $string .= "    '{$psr}' => [\n";

                foreach ($a as $namespace => $src) {

                    if (is_array($src)) {
                        foreach ($src as &$s) {
                            $s = "'{$src}'";
                        }

                        $src = '[' . implode(',', $src) . ']';
                    } else {
                        $src = "'{$src}'";
                    }

                    $namespace = addslashes($namespace);

                    $string .= "        '{$namespace}' => {$src},\n";
                }

                $string .= "    ]\n";
            }

        }

        $string .= "];";

        return $string;
    }

    /**
     * Get directories (absolute paths)
     *
     * @param string $path
     * @return array
     */
    public function directories($path = '')
    {
        $directories = [];

        if (is_dir($path)) {

            // This is much faster than glob($path . '/*');
            $iterator = new \DirectoryIterator($path);

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isDot() and $fileInfo->isDir()) {
                    $directories[] = $fileInfo->getPathname();
                }
            };
        }

        return $directories;
    }

    /**
     * Get package composer.json contents as an object
     *
     * @param $package
     * @return mixed
     */
    public function getPackageComposerJson($package)
    {
        return json_decode(file_get_contents($package . '/' . $this->composerJson));
    }

    /**
     * Get the generated filename
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->filename;
    }
}
