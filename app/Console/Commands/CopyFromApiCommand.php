<?php
/**
 * File defines class for a console command to send
 * email notifications to users
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;


/**
 * Class deletePostsCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class CopyFromApiCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "api:copy";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Copy all from API";

    private $copyArray = [
        //Models are a bit fucked. Nevermind.
        //        'models'    => [
        //            'source_dir' => 'models',
        //            'files'      => [
        //                'Field',
        //                'Resource',
        //                'User',
        //                'Website',
        //            ],
        //            'target_dir' => 'Models',
        //            'callback'   => [],
        //            'finalize'   => []
        //        ],


        //carefullll.!!
        'rootfiles' => [
            'source_dir' => '',
            'files'      => ['resources'],
            'target_dir' => 'Resources',
            'callback'   => ['fixApp'],
            'finalize'   => []
        ],

//        'config' => [
//            'source_dir'   => 'config',
//            'files'        => '*',
//            'files_except' => [
//                'database',
//                'package',
//                'queue',
//                'cache',
//                'remote',
//                'services',
//                'sessions',
//                'settings',
//                'styles',
//                'workbench',
//                'blacklist',
//                'auth',
//                'compile',
//                'app',
//                'cache',
//                'testing',
//                'remote',
//                'configuration',
//                'workbench',
//                //manually fixed
//                'cdn',
//                'mail',
//                'resource_healthcarech',
//                'resource_knip',
//                'resource_multisafepay',
//                'resource_nearshoring',
//                'resource_parkandfly',
//                'resource_parkingci',
//                'resource_parkingpro',
//                'resource_paston',
//                'resource_rolls',
//                'resource_schipholparking',
//                'resource_taxiboeken',
//                'resource_taxitender',
//                'resource_zorgweb',
//            ],
//            'target_dir'   => '../config',
//            'callback'     => [],
//            'finalize'     => []
//        ],


        'helpers'    => [
            'source_dir' => 'interfaces',
            'files'      => '*',
            'target_dir' => 'Interfaces',
            'callback'   => [],
            'finalize'   => []
        ],
        'resources'  => [
            'source_dir' => 'resources',
            'files'      => '*',
            'target_dir' => 'Resources',
            'callback'   => [
                'fixValidator',
                'fixQueries',
                'fixConfig'
            ],
            'finalize'   => []
        ],
        'interfaces' => [
            'source_dir' => 'helpers',
            'files'      => [
                'Healthcare2018Helper',
                'FieldHelper',
                'WebsiteHelper',
                'IAKHelper',
                'TranslationHelper',
                'ContractHelper',
                'ElipslifeHelper',
                'ResourceFilterHelper',
                //'CacheHelper',
            ],
            'target_dir' => 'Helpers',
            'callback'   => [],
            'finalize'   => []
        ],

        'listeners' => [
            'source_dir' => 'listeners/Resources2',
            'files'      => '*',
            'target_dir' => 'Listeners',
            'callback'   => ['fixKeyBy', 'fixApp', 'runningInConsole', 'commentBrokeEvents', 'fixRoutes', 'fixConfig'],
            'finalize'   => ['subscribeListeners']
        ],
    ];

    const API_LOCATION = '../api/';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach($this->copyArray as $name => $settings){
            $this->info('Copying: ' . $name);
            if(is_array($settings['files'])){
                foreach($settings['files'] as $file){
                    $sourceDir  = $settings['source_dir'] ? $settings['source_dir'] . '/' : '';
                    $targetDir  = $settings['target_dir'] ? $settings['target_dir'] . '/' : '';
                    $remoteFile = base_path(self::API_LOCATION . 'app/' . $sourceDir . $file . '.php');
                    $localFile  = base_path('app/' . $targetDir . $file . '.php');
                    self::copyCallback($remoteFile, $localFile, $settings['callback']);
                }
                continue;
            }

            if($settings['files'] != '*'){
                continue;
            }
            $fileNames = $this->cpy(base_path(self::API_LOCATION . 'app/' . $settings['source_dir']), base_path('app/' . $settings['target_dir']), $settings['callback'], $settings['files_except'] ?? []);

            foreach($settings['finalize'] as $finalizer){
                $this->{$finalizer}($fileNames);
            }
        }
    }

    /**
     * @param $source
     * @param $dest
     * @param array $callback
     *
     * @param array $filesExcept
     *
     * @return array
     */
    private function cpy($source, $dest, array $callback, array $filesExcept)
    {
        if(is_dir($source)){
            if(str_contains($source, $filesExcept)){
                return [];
            }
            $dir_handle     = opendir($source);
            $filesProcessed = [];
            while($file = readdir($dir_handle)){
                if(str_contains($file, $filesExcept)){
                    continue;
                }
                if($file != "." && $file != ".."){
                    if(is_dir($source . "/" . $file)){
                        if( ! is_dir($dest . "/" . $file)){
                            mkdir($dest . "/" . $file);
                        }
                        $this->cpy($source . "/" . $file, $dest . "/" . $file, $callback, $filesExcept);
                    }else{
                        $filesProcessed[] = $file;
                        self::copyCallback($source . "/" . $file, $dest . "/" . $file, $callback);
                    }
                }
            }
            closedir($dir_handle);
            return $filesProcessed;
        }else{
            self::copyCallback($source, $dest, $callback);
        }
    }

    /**
     * @param String $source
     * @param String $destination
     * @param array $callback
     */
    public static function copyCallback(String $source, String $destination, array $callback)
    {
        $file = file_get_contents($source);
        foreach($callback as $function){
            $file = self::{$function}($file);
        }
        file_put_contents($destination, $file);
    }

    //CALLBACKS

    /**
     * Fix compatibility issues
     * $resource->fields->keyBy('name') to collect($resource->fields)->keyBy('name');
     *
     * @param string $file
     *
     * @return string
     */
    public static function fixKeyBy(string $file): string
    {
        $regexp      = '/(.+)=\s(.+)->keyBy\((\'name\')\)/';
        $replacement = '$1= collect($2)->keyBy($3)';
        return preg_replace($regexp, $replacement, $file);
    }

    /**
     * App::make( -> app(
     *
     * @param string $file
     *
     * @return string
     */
    public static function fixApp(string $file): string
    {
        $replaceArray = [
            'App::make('         => 'app(',
            'App::offsetExists(' => 'app(',
            'App::bind('         => '$app->bind(',
        ];
        return self::replaceByArray($file, $replaceArray);
    }


    public static function fixValidator(string $file): string
    {
        $replaceArray = [
            'public function __construct(Validator $validator)' => 'public function __construct()',
            '$this->validator = $validator;//tag_to_fix'        => '$this->validator = new \Komparu\Input\Validation\SiriusValidator(new \Sirius\Validation\Validator(), new \Komparu\Input\Rule\RuleFactory(new \Komparu\Resolver\Resolver()));'
        ];
        return self::replaceByArray($file, $replaceArray);
    }

    public static function fixQueries(string $file): string
    {
        $find    = '$query->get()';
        $replace = '$query->get()->toArray()';
        $tmp     = str_replace($find, $replace, $file);
        return $tmp;
    }

    public static function runningInConsole(string $file): string
    {
        $replaceArray = [
            '\App::runningInConsole()' => '(strpos(php_sapi_name(), \'cli\') !== false)',
            'App::runningInConsole()'  => '(strpos(php_sapi_name(), \'cli\') !== false)',
        ];
        return self::replaceByArray($file, $replaceArray);
    }

    public static function fixRoutes(string $file): string
    {
        $replaceArray = [
            'resource.data.index'    => 'resource.index',
            'resource.data.show'     => 'resource.show',
            'resource.data.store'    => 'resource.store',
            'resource.data.destroy'  => 'resource.destroy',
            'resource.data.truncate' => 'resource.truncate',
        ];
        return self::replaceByArray($file, $replaceArray);
    }

    public static function fixConfig(string $file): string
    {
        //((app()->configure('resource_zorgweb')) ? '' : config('resource_zorgweb.settings.url'))

        preg_match_all('/Config::get\(\'([a-z_]+)\.(.+)\'\)/U', $file, $matches);
        if(isset($matches[1], $matches[1][0])){
            $counter = 0;
            $replaceArray = [];
            foreach( $matches[1] as $resource){
                $toReplace = $matches[0][$counter];
                $totalResource = $resource.'.'.$matches[2][$counter];
                $replaceArray[$toReplace] = '((app()->configure(\''.$resource.'\')) ? \'\' : config(\''.$totalResource.'\'))';
                $counter ++;
            }
            return self::replaceByArray($file, $replaceArray);
        }
        return $file;
    }


    /**
     * TODO: fix these events
     * Events that are broken, disable them
     *
     * @param string $file
     *
     * @return string
     */
    public static function commentBrokeEvents(string $file): string
    {
        $brokeEvents = [
            '$events->listen(\'resource.process.input\', [$this, \'setLanguageConditions\'])'
        ];

        $tmp = $file;
        foreach($brokeEvents as $brokeEvent){
            $tmp = str_replace($brokeEvent, '//' . $brokeEvent, $tmp);
        }

        return str_replace('App::offsetExists(', 'app(', $tmp);
    }

    private static function replaceByArray(string $string, array $replaceArray): string
    {
        $tmp = $string;
        foreach($replaceArray as $search => $replace){
            $tmp = str_replace($search, $replace, $tmp);
        }
        return $tmp;
    }
    //FINALIZERS


    /**
     * Add all the files to the app.php
     *
     * @param array $fileNames
     */
    private function subscribeListeners(array $fileNames)
    {
        $ignoreList = ['DefaultListener', 'PermissionListener'];
        sort($fileNames);
        $appPhp   = base_path('bootstrap/app.php');
        $contents = file_get_contents($appPhp);
        $regexp   = '/\/\/START RESOURCE2(.+)\/\/STOP RESOURCE2/s';

        $replacement = '//START RESOURCE2' . PHP_EOL;
        foreach($fileNames as $fileName){
            preg_match('/(.+).php/', $fileName, $match);
            $className = $match[1];
            if(in_array($className, $ignoreList)){
                continue;
            }
            if( ! isset($className)){
                $this->error('Skip non php file ' . $fileName);
                continue;
            }
            $replacement .= 'Event::subscribe(\\App\\Listeners\\Resources2\\' . $className . '::class);' . PHP_EOL;
        }
        $replacement .= '//STOP RESOURCE2';

        file_put_contents($appPhp, preg_replace($regexp, $replacement, $contents));
    }


}
