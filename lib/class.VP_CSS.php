<?php
//required to implement VP_Module
require_once('class.VP_Module.php');

/**
 * VP_CSS is a plugin for the Vanishing Point framework to optimize the serving of Cascading Style Sheets to clients. VP_CSS was built implementing
 * the VP_Module interface and implements CSSTidy.
 * 
 * @link http://www.net-perspective.com/ Net Perspective Website
 * @link http://csstidy.sourceforge.net/ CSS Minifier
 * 
 * @version 0.1.0
 * @author Andrew Ellis <aellis@net-perspective.com>
 * @copyright Copyright (c) 2008, Net Perspective, LLC
 * @license http://www.opensource.org/licenses/mit-license.php MIT License 
 * 
 * @package VanishingPoint
 * @subpackage Modules
 */
class VP_CSS implements VP_Module
{

	/**
	* Holds the object's file name (will be modified by a call to render)
	* 
	* @var String
	*/
	private $file;
	
	/**
	* Holds the object's unaltered file name (the file name passed to the contructor)
	* 
	* @var String
	*/
	private $unaltered;
	
	/**
	* Holds the object's version
	* 
	* @var String
	*/
	private $version;
	
	/**
	* Holds the the value to be placed in title=""
	* 
	* @var String
	*/
	private $title;
	
	/**
	* Holds the the value to be placed in media=""
	* 
	* @var String
	*/
	private $media;
	
	/**
	* Holds the the content of the file
	* 
	* @var String
	*/
	private $content;
	
	/**
	*W When true, this file will be combined with others in the group
	* 
	* @var Boolean
	*/
	private $combine;
	
	/**
	* Holds the the path to be prepeneded to the file name when displayed in the browser
	* 
	* @var String
	*/
	private static $HTTPPath;
	
	/**
	* Holds the the path to be prepended to the file name when accessing the file on the server
	* 
	* @var String
	*/
	private static $serverPath;
	
	/**
	* Constant to define the extension for files in this module
	* 
	* @var String
	*/
	const BASE_EXTENSION = ".css";
	
	/**
	* Constant to define the extension for files in this module when combined (is followed by BASE_EXTENSION)
	* 
	* @var String
	*/
	const COMBINED_EXTENSION = ".comb";

	/**
	* $file and $version are the only required parameters for the constructor.
	*
	* <code>
	* <?php
	* //create a VP_CSS object to be combined, with title "standard" and media type "micro"
	* $css_obj = new VP_CSS("file.css","1-0-0",true,"standard","micro"); 
	* ?>
	* </code>
	*
	* @param String $file The name of the file to be optimized
	* @param String $version The version of the file [used for caching purposes]
	* @param Boolean $combine Sets whether the file should be combined with others from the same group (defaults to false)
	* @param String $title Holds the value to be placed in title="" (defaults to empty)
	* @param String $media Holds the value to be placed in media="" (defaults to empty)
	*/
	public function __construct($file, $version, $combine=false, $title="", $media="")
	{
		$this->file = $this->unaltered = $file;
		$this->version = $version;
		$this->title = $title;
		$this->media = $media;
		$this->combine = $combine;
	}
	
	
	/**
	* setServerPath() accepts a string to use as the prepended path for files to be accessed serverside
	*
	* @param String $path The path to precede filenames for php to be able to access the file
	*/
	public static function setServerPath($path)
	{
		self::$serverPath = $path;
	}
	
	/**
	* setHTTPPath() accepts a string to use as the prepended path for files to be accessed clientside
	*
	* @param String $path The path to precede filenames for clients (such as browsers) to be able to access the file
	*/
	public static function setHTPPPath($path)
	{
		self::$HTTPPath = $path;
	}
	
	/**
	* render() runs the CSSTidy CSS minifier along with custom regular expressions to remove 
	* comments and uses css includes ( @include url ) to combine multiple CSS files into a single
	* file. render() accepts a single parameter, however; this is only so that combine() can share
	* code with render(). 
	*
	* @param Boolean $combine Defaults to false
	*/
	public function render($combine=false)
	{		
		$this->load();
		
		
		//Here is where I do my minifying since csstidy didn't do everything I wanted
		//Order is VERY imporant here... don't modify these
		
			
		//remove multi-line 
		$this->contents = preg_replace("~/\*.*?\*/~","",$this->contents);
		
		$regex = "/@import url\(\"(.*?)\"\);/is";
		$tmp = array();
		preg_match_all($regex,$this->contents, $tmp);		
		for($a=0; $a<count($tmp[0]); $a++)
		{
			if(preg_match("~^/~",$tmp[1][$a]))
				$tmp[1][$a] = substr($tmp[1][$a],1);
			if(file_exists(self::$serverPath.$tmp[1][$a]))
				$this->contents = preg_replace('~'.preg_quote($tmp[0][$a]).'~',file_get_contents(self::$serverPath.$tmp[1][$a]),$this->contents);
			else if(file_exists('./'.$tmp[1][$a]))
				$this->contents = preg_replace('~'.preg_quote($tmp[0][$a]).'~',file_get_contents('./'.$tmp[1][$a]),$this->contents);
		}
		
		//remove multi-line 
		$this->contents = preg_replace("~/\*.*?\*/~","",$this->contents);
		preg_match_all($regex,$this->contents, $tmp);		
		for($a=0; $a<count($tmp[0]); $a++)
		{
			if(preg_match("~^/~",$tmp[1][$a]))
				$tmp[1][$a] = substr($tmp[1][$a],1);
			if(file_exists(self::$serverPath.$tmp[1][$a]))
				$this->contents = preg_replace('~'.preg_quote($tmp[0][$a]).'~',file_get_contents(self::$serverPath.$tmp[1][$a]),$this->contents);
			else if(file_exists('./'.$tmp[1][$a]))
				$this->contents = preg_replace('~'.preg_quote($tmp[0][$a]).'~',file_get_contents('./'.$tmp[1][$a]),$this->contents);
		}
		
		
		require_once("csstidy/class.csstidy.php"); 
		$css = new csstidy();
		$css->set_cfg('preserve_css',true);
		$css->parse($this->contents);
		$this->contents = $css->print->plain();
		
		//Here is where I do my minifying since csstidy didn't do everything I wanted
		//Order is VERY imporant here... don't modify these
		
		//remove single line comments
		$this->contents = preg_replace("~//(.*)~","",$this->contents);
		
		//remove new lines and carrige returns
		$this->contents = preg_replace("/[\n\r]/","",$this->contents);
		
		//remove multi-line comments
		$this->contents = preg_replace("~/\*.*?\*/~","",$this->contents);
		
		
		$this->file = preg_replace("/\.css/",".v".$this->version.self::BASE_EXTENSION,$this->file);
		
		if(!$combine)
		{
			$this->save();		
			$this->clean();
		}
		return '<link href="'.self::$HTTPPath.$this->file.'" rel="stylesheet" type="text/css" title="'.$this->title.'" media="'.$this->media.'"/>';
	}
	
	/**
	* Returns the unaltered file name.
	*/
	public function getFileName()
	{
		return self::$HTTPPath.$this->unaltered;
	}
	
	/**
	* combine() takes a group of files and makes a single file out of them to optimize HTTP Request times.
	* 
	* $files is the array of VP_CSS objects passed into combine() to be combined into a single file. This is
	* handled completely by the Vanishing Point framework.
	* 
	* &$rendered is a pointer to the array of already rendered files. combine() adds the unaltered file names to this
	* when it adds a file to the combination file so as to keep it from being repeated as a packed or regular javascript
	* file.
	* 
	* @param Array $files an array of VP_CSS objects
	* @param Array &$rendered an array of strings passed by reference
	*/
	public static function combine(Array $files, Array &$rendered )
	{
		$names = array();
		$contents = "";
		
		$regex = "";
		foreach($files as $file)
		{
			if($file->combine)
			{
				//$file->render(true);
				$rendered[] = $file->getFileName();
				$names[] = preg_replace("/".self::BASE_EXTENSION."/",".v".$file->version,$file->unaltered);	
				$regexes[] = preg_replace("/".self::BASE_EXTENSION."/",".v.*",$file->unaltered);
				//$contents .= $file->contents;
			}
		}
		$file = "";
		
		$size = count($names)-1;
		
		$regex = "=". implode("_",$regexes).".comb.css" . "=";
		$file = implode("_",$names);
		$file .= self::COMBINED_EXTENSION.self::BASE_EXTENSION;
		if(!file_exists(self::$serverPath.$file))
		{		
			foreach($files as $f)
			{
				if($f->combine)
				{
					$f->render(true);
					$contents .= "\n". $f->contents;
				}
			}
			
			if(!file_put_contents(self::$serverPath.$file,$contents))
			{
				throw new Exception("Could not write to file: " . self::$serverPath.file);
			}
		}
		
		
		foreach( new DirectoryIterator( self::$serverPath ) as $f )
		{
			$fileDir = $f->getPath();
			$fileName = $f->getFilename();			
			if( $f->isFile() ) 			
			{
				if(preg_match($regex,$fileDir."/".$fileName) > 0 && $fileDir."/".$fileName != $file && $fileName != $file)
				{
					
					//remove any file that matches
					@unlink($fileDir . "/" . $fileName);
				}
			}
		}			
		
		return '<link href="'.self::$HTTPPath.$file.'" rel="stylesheet" type="text/css"/>';
	}

	/**
	* Returns the <link> include with the unaltered file, for debugging purposes.
	*/	
	public function debug()
	{
		return '<link href="'.self::$HTTPPath.$this->file.'" rel="stylesheet" type="text/css" title="'.$this->title.'" media="'.$this->media.'"/>';
	}
	
	/**
	* Checks for existance of the file and loads the contents into a variable. Throws an exception if the file does not exist.
	*/
	private function load()
	{
		if( file_exists(self::$serverPath.$this->file) )
		{
			//get the contents
			$this->contents = file_get_contents(self::$serverPath.$this->file);
			return true;
		}
		else
		{
			throw new Exception("File not found ('".self::$serverPath.$this->file."')");
		}
	}
	
	/**
	* Puts the data from the contents variable into a file. Throws an exception if this fails.
	*/
	private function save()
	{
		//write the file and throw an exception if we have an issue
		if( !file_put_contents(self::$serverPath.$this->file,$this->contents) )
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
		$path = dirname(self::$serverPath.$this->file);
		$file = basename($this->unaltered, self::BASE_EXTENSION);
		
		//build regular expressions based on what has been done to the file
		$regex =  "=" .  preg_replace("/\./","\.", $file . ".v") . ".*";		
		$file .= ".v" . $this->version;
		
		
		
		$regex .= self::BASE_EXTENSION . '=';
		$file .= self::BASE_EXTENSION;
		
		
		//iterate through the directory and search for possible matches to remove.
		foreach( new DirectoryIterator( $path ) as $f )
		{
			$fileDir = $f->getPath();
			$fileName = $f->getFilename();			
			if( $f->isFile() ) 			
			{
				//check to see if the file is versioned and should be removed
				if(preg_match($regex,$fileDir."/".$fileName) > 0 && $fileDir."/".$fileName != $file && preg_match("/".$this->version."/", $fileName) == 0)
				{
					//remove any file that matches
					@unlink($fileDir . "/" . $fileName);
				}
			}
		}			
	}
}
?>