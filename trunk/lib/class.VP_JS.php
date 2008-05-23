<?php
//required to implement VP_Module
require_once('class.VP_Module.php');

/**
 * VP_JS is a plugin for the Vanishing Point framework to optimize the serving of Javascript to clients. VP_JS was built implementing
 * the VP_Module interface and employs Dean Edwards' JSPacker.
 * 
 * @link http://www.net-perspective.com/ Net Perspective Website
 * @link http://dean.edwards.name/packer/ JSPacker
 * 
 * @version 0.1.0
 * @author Andrew Ellis <aellis@net-perspective.com>
 * @copyright Copyright (c) 2008, Net Perspective, LLC
 * @license http://www.opensource.org/licenses/mit-license.php MIT License 
 * 
 * @package VanishingPoint
 * @subpackage Modules
 */
class VP_JS implements VP_Module
{
	/**
	* Holds the object's version
	* 
	* @var String
	*/
	private $version = "";
	
	/**
	* Holds the objects' file name for output, modified by render() and combine()
	* 
	* @var String
	*/
	private $file = "";
	
	/**
	* Holds the objects' file name, not modified by render() and combine()
	* 
	* @var String
	*/
	private $unaltered = "";
	
	/**
	* Holds if the file is to be included in a combination file when possible
	* 
	* @var boolean
	*/
	private $combine = "";
	
	/**
	* Holds the object's mod (0 -7)
	* 
	* <pre>
	* Mode     Version     Move     Pack
	* 
	* 0   	   No          No       No
	* 1   	   No          No       <strong>Yes</strong>
	* 2   	   <strong>Yes</strong>         No       No  
	* 3   	   <strong>Yes</strong>         No       <strong>Yes</strong>  
	* 4   	   No          <strong>Yes</strong>      No  
	* 5   	   No          <strong>Yes</strong>      <strong>Yes</strong>  
	* 6   	   <strong>Yes</strong>         <strong>Yes</strong>      No  
	* 7   	   <strong>Yes</strong>         <strong>Yes</strong>      <strong>Yes</strong>  
	* </pre>
	* @var integer
	*/
	private $mode = "";
	
	/**
	* Holds in array form the options (pack, version, move) for the file
	* 
	* @var Array
	*/
	private $options = array();
	
	/**
	* The default HTTP path.
	*/
	public static $HTTPPath = "/scripts/";
	
	/**
	* The HTTP path to prepend to the file name after the file has been moved.
	*/
	public static $HTTPPath_cache = "/Vanishing%20Point/scripts/VP_Cache/";
	
	/**
	* The default path to prepend to the file name for writing to it on the server.
	*/
	public static $serverPath = "scripts/";
	
	/**
	* The path to prepend to the file name for writing to it on the server if the file has to be moved.
	*/
	public static $serverPath_cache = "scripts/VP_Cache/";	
	
	/**
	* The default extension for files processed by VP_JS.
	*/
	const BASE_EXTENSION = ".js";
	
	/**
	* The extension to be added to packed files.
	*/
	const PACKED_EXTENSION = ".pack.js";
	
	/**
	* The extension to be added to combined files.
	*/
	const COMBINE_EXTENSION=  ".comb.js";
	
	/**
	* The option to be passed to the VP_JS constructor for packing.
	*/
	const PACK = 1;
	
	/**
	* The option to be passed to the VP_JS constructor for versioning.
	*/
	const VERSION = 2;	
	
	/**
	* The option to be passed to the VP_JS constructor for moving.
	*/
	const MOVE = 4;
	
	/**
    * $file and $version are required for the contructor. $file should be a path that php can use to
	* access the file. $version is the current version of the javascript file. 
	* 
	* $mode is set to default to 7. The integer value of 7 corseponds to a file that is packed and minified, 
	* moved to the cache directory, and has a version appeneded to it. 
	* While $mode can be set by an integer 0 through 7, VP_JS provides class constants that allow for more
	* readable control of these modes. By using a bitwise OR mode can be combined.
	* 
	* $combine takes a boolean for if the file should be included in the combination file for that group. In order to be
	* combined a file must have both $combine set to true and a $mode of 7. Combination of files is where VP_JS does the 
	* majority of the optimazation and should be used wherever possible.
	* 
	* <code>
	* <?php
	* //create a packed and versioned VP_JS object
	* $js_obj = new VP_JS("file.js","1-0-0",VP_JS:PACK|VP_JS:VERSION); 
	* ?>
	* </code>
	* 
	* @param string $file
	* @param string $version
	* @param int $mode
	* @param boolean $combine
	*/
	public function __construct($file, $version, $mode = 7, $combine = true)
	{
		$this->file = $this->unaltered = $file;
		$this->version = $version;
		$this->combine = $combine;
		$this->mode = $mode;
		
		//convert mode into binary, reverse it, pad it up to 3 places (needed for numbers less than 4), re-reverse it(set it straight)
		//then distribute into the options array
		$modeBin = strrev(str_pad(strrev(decbin($mode)),3,0));
		$this->options['pack'] = $modeBin[2];
		$this->options['version'] = $modeBin[1];
		$this->options['move'] = $modeBin[0];
	}
	
	/**
	* Returns the unaltered file name.
	*/
	public function getFileName()
	{
		return $this->unaltered;
	}
	
	
	public function setServerPath($serverPath, $serverPath_cache="")
	{
		self::$serverPath = $serverPath;
		if($serverPath_cache == "")
			self::$serverPath_cache = $serverPath . "/VP_Cache";
		else
			self::$serverPath_cache = $serverPath_cache;
	}
	
	
	public function setHTTPPath($HTTPPath, $HTTPPath_cache="")
	{
		self::$HTTPPath = $HTTPPath;
		if($HTTPPath_cache == "")
			self::$HTTPPath_cache = $HTTPPath . "/VP_Cache";
		else
			self::$HTTPPath_cache = $HTTPPath_cache;
	}
	
	/**
	* Returns the <script> include with the correct file name and path after the file is packed/versioned/moved.
	*/
	public function render()
	{	
		//file names should not have a leading /
		$path = "/" . $this->file;
		
		//if the file is to be moved
		if($this->options['move'] == 1)
			$path = $this->move();
			
		//if the file is to be versioned
		if($this->options['version'] == 1)
			$path = $this->version();
		
		//if the files is to be packed
		if($this->options['pack'] == 1)
			$path = $this->pack();
		
		//if there is something to clean, do it
		if( $this->options['version'] == true )
			$this->clean();
		
		return '<script type="text/javascript" src="'.$path.'"></script>' . "\n";
	}
	
	/**
	* Returns the <script> include with the unaltered file, for debugging purposes.
	*/
	public function debug()
	{
		return '<script type="text/javascript" src="/'.$this->unaltered.'"></script>' . "\n";
	}
	
	/**
	* combine() takes a group of files and makes a single file out of them to optimize HTTP Request times.
	* 
	* $files is the array of VP_JS objects passed into combine() to be combined into a single file. This is
	* handled completely by the Vanishing Point framework.
	* 
	* &$rendered is a pointer to the array of already rendered files. combine() adds the unaltered file names to this
	* when it adds a file to the combination file so as to keep it from being repeated as a packed or regular javascript
	* file.
	* 
	* @param Array $files an array of VP_JS objects
	* @param Array &$rendered an array of strings passed by reference
	*/
	public static function combine(Array $files, Array &$rendered )
	{
		$path = "";
		
		$names = array();
		$tmp = "";
		
		//iterate through all the files
		foreach($files as $file)
		{
			//if it can be combined
			if($file->combine && $file->mode == 7 && !in_array($file->file, $rendered))
			{
				//get the name
				$names[] = basename($file->file, self::BASE_EXTENSION) . $file->version;
				//build a regex for cleaining purposes
				$regexes[] = preg_replace("/\./","\.", basename($file->file, self::BASE_EXTENSION) ) . ".[a-zA-Z0-9\-]+";
				//add to the rendered list		
				$rendered[] = $file->file;
			}
		}
		
		//if there is something to do
		if(count($names) > 0 )
		{
			//create the actual path
			$path = self::$serverPath_cache .  implode("_",$names) . self::COMBINE_EXTENSION;
			//create the actual regular expression
			$regex =  preg_replace("/\./","\.", self::$serverPath_cache)  .  implode("_",$regexes) . preg_replace("/\./","\.", self::COMBINE_EXTENSION );
			
			//no need to remake the file
			if(!file_exists($path))
			{
				//iterate again
				foreach($files as $file)
				{
					//re-check
					if($file->combine && $file->mode == 7 )
					{
						//pack and get the contents
						$file->pack(true);
						$tmp .= $file->contents;	
					}
				}
				//all done, write it out and throw an exception if we have an issue
				if( !file_put_contents($path,$tmp ))
				{
					 throw new Exception("Cannot save file ('".$path."')");
				}
			}
			
			//cleaning time, see documentation for clean()
			foreach( new DirectoryIterator( dirname($regex) ) as $f )
			{
				$fileDir = $f->getPath();
				$fileName = $f->getFilename();			
				if( $f->isFile() ) 			
				{					
					if(preg_match("=" . "^" . basename($regex) . "=",$fileName) > 0 && $fileName != basename($path) /*&& preg_match("/".$this->version."/", $fileName) == 0*/)
					{
						@unlink($fileDir . "/" . $fileName);
					}
				}
			}			
			
			$path = self::$HTTPPath_cache .  implode("_",$names) . self::COMBINE_EXTENSION;
			
			return '<script type="text/javascript" src="'.$path.'"></script>' . "\n";
		}
	}
	
	/**
	* Copies the file to the cache directory and returns the correct filename and path for HTTP requests.
	*/
	private function move()
	{
		//create the new filename
		$newfile =  basename(str_replace('/','-',$this->file), self::BASE_EXTENSION) . self::BASE_EXTENSION;
		//copy it
		copy($this->file,  self::$serverPath_cache . $newfile);
		//modify our var
		$this->file = self::$serverPath_cache . $newfile;
		//return
		return self::$HTTPPath_cache. $newfile;
	}
	
	/**
	* Adds the version to the filename for caching purposes and returns the correct filename and path for HTTP requests.
	*/
	private function version()
	{		
		//genereate the new filename
		$newfile = preg_replace("/js/i", 'v'. $this->version . ".js", $this->file);
		
		//if the file exists, we would be simply replacing like with like (since the version is the same)
		//so this only is executed if the file doesnt exist
		//if it was moved, rename it since it was copied to the cache dir
		//if it wasn't moved, the original needs to be preserved, so copy it
		if($this->options['move'] == 1 && !file_exists($newfile))
			rename($this->file, $newfile);
		else if(!file_exists($newfile))	
			copy($this->file, $newfile);	
			
		//modify the filename
		$this->file = $newfile;
		//if was moved we need a new path
		if($this->options['move'])
			$newfile = self::HTTPPATH_CACHE .  basename($newfile);
		else
			$newfile = "/" . $this->file;
		//return
		return $newfile;
	}
	
	/**
	* Run's Dean Edwards' JSPacker on the file to minify the Javascript source and returns the correct filename and path for HTTP requests.
	* 
	* Functionality is provided for encoding and further minifying the Javascript, however; this has been shown to increase page 
	* load time do to the extra processing of Javascript client side. The code for this has been commented out and may be uncommented
	* for use. This encoding includes an algorithm to use all 4 methods and find the smallest.
	*/
	private function pack($combine = false)
	{		
		if($this->load())
		{
			//get the packer, no use for a global require... let's be efficient!
			require_once('jsPacker/class.JavaScriptPacker.php');
			
			//if not managed by combine(), create the filename
			if(!$combine)
				$this->file = preg_replace("/\.js/i",self::PACKED_EXTENSION,$this->file);
			
			$newfile = "";
			
			//if it was cached
			if(preg_match("-".self::$serverPath_cache."-",$this->file) > 0)
			{
				//build the new filename and path
				$newfile = self::HTTPPATH_CACHE . preg_replace("-".self::SERVERPATH_CACHE."-","",$this->file);
			}
			//if it ws not cached
			else if(preg_match("-".self::$serverPath."-",$this->file) > 0)
			{
				//build the new filename and path
				$newfile = self::$HTTPPath . preg_replace("-".self::$serverPath."-","",$this->file);
			}
			//hmm... neither. we'll skip that
			else
			{
				//looks like we were already good to go
				$newfile = $this->file;
			}
			
			//if we arent combining it, and the file already exists, return and save processor time
			if(!$combine && file_exists($this->file))
			{
				return $newfile;
			}
		
			//non-encoded minification
			$packerNone = new JavaScriptPacker($this->contents, 'None', true, false);
			$packedNone = $packerNone->pack();
			
			
			//encoded minification, uncomment the algorithms you would like to employ
			
			/*
			$packer10 = new JavaScriptPacker($this->contents, 10 , true, false);
			$packed10 = $packer10->pack();
			
			$packer62 = new JavaScriptPacker($this->contents, 62 , true, false);
			$packed62 = $packer62->pack();
			
			$packer95 = new JavaScriptPacker($this->contents, 95 , true, false);
			$packed95 = $packer95->pack();
			*/
			
			
			//create an array based on length of output file to find the smallest
			
			$sizes[strlen($packedNone)] = "packedNone";
			/*
			$sizes[strlen($packed10)] = "packed10";
			$sizes[strlen($packed62)] = "packed62";
			$sizes[strlen($packed95)] = "packed95";
			*/
			
			//Reverse sort by key to order by filesizes
			krsort($sizes);
				
			$tmp = array_pop($sizes);
			$this->contents = $$tmp;	
			
			//if it isn't combining, save it, combine() takes care of this for us
			if(!$combine)
				$this->save();
		}
		return $newfile;
	}
	
	/**
	* Checks for existance of the file and loads the contents into a variable. Throws an exception if the file does not exist.
	*/
	private function load()
	{
		//make sure it exists
		if( file_exists($this->file) )
		{
			//get the contents
			$this->contents = file_get_contents($this->file);
			return true;
		}
		else
		{
			throw new Exception("File not found ('".$this->file."')");
		}
	}
	
	/**
	* Puts the data from the contents variable into a file. Throws an exception if this fails.
	*/
	private function save()
	{
		//write the file and throw an exception if we have an issue
		if( !file_put_contents($this->file,$this->contents) )
		{
			throw new Exception("Cannot save file ('".$this->file."')");
		}
	}

	/**
	* Removes old versions of files that have been versioned. Any non-versioned file will be omited due to inability to
	* resolve if it is old or not. 
	*/
	private function clean()
	{
		//get original info
		$path = dirname($this->file);
		$file = basename($this->unaltered, self::BASE_EXTENSION);
		
		//build regular expressions based on what has been done to the file
		$regex =  "=" .  preg_replace("/\./","\.", $file . ".v") . ".*";		
		$file .= ".v" . $this->version;
		
		if($this->options['pack'])
		{
			$regex .= "[^comb]\.pack";
			$file .= ".pack";
		}
		else
		{
			$regex .= "[^comb][^pack]";
		}
		
		$regex .= "\.js=";
		$file .= ".js";
				
		//iterate through the directory and search for possible matches to remove.
		foreach( new DirectoryIterator( $path ) as $f )
		{
			$fileDir = $f->getPath();
			$fileName = $f->getFilename();			
			if( $f->isFile() ) 			
			{
				//check to see if the file is versioned and should be removed
				if(preg_match($regex,$fileName) > 0 && $fileName != $file && preg_match("/".$this->version."/", $fileName) == 0)
				{
					//remove any file that matches
					@unlink($fileDir . "/" . $fileName);
				}
			}
		}			
	}
}
?>