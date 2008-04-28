<?php
/**
 * VP_Module is an interface to be implemented by all Vanishing Point plugins. The interface defines what functions MUST
 * be defined and how they should interact with Vanishing Point. 
 * 
 * All nessesary data should be passed to Vanishing Point plugins via their constructor.
 * 
 * To create a new Vanishing Point plugin all 4 interface functions and a constructor must be defined.
 * If a particular function is not going to be implemented, for example combine(), it must still be declared
 * within the context of the plugin, however; it's function body can be left empty. 
 * 
 * getFileName() must be created and must return the unaltered string containing the filename. 
 * Failure to do so will result in duplication of files by Vanishing Point. With getFileName() as the
 * exception, all functions may be defined as desired as long as the function structure (parameters and 
 * return values) do not deviate. 
 * 
 * @link http://www.net-perspective.com/ Net Perspective Website
 * 
 * @version 1.0.0
 * @author Andrew Ellis <aellis@net-perspective.com>
 * @copyright Copyright (c) 2008, Net Perspective, LLC
 * @license http://www.opensource.org/licenses/mit-license.php MIT License 
 * 
 * @package VanishingPoint
 */
interface VP_Module
{
	/**
	* render() should return a string, modified as desired, exactly as it is to be displayed in the HTML as
	* well as doing any nessesary operations on the file(s).
	*/
	public function render();
	
	/**	
	* getFileName() should return the original, unaltered filename to be used by Vanishing Point, as a string.
	*/
	public function getFileName();
	
	/**
	* combine() should return a string to be echod on the page. This string should be the location of the combination file
	* in whatever context is needed. (e.g. Javascript needs <script></script> tags, an image would use an <img/> tag.) Also
	* combine() should created the combination file.
	* 
	* @param Array $files is an array of the files in the current group to be combined.
	* @param Array &$rendered is the array of "already rendered" files. The unaltered file name should be added to 
	* this array ($rendered[] = $unaltered) so as to not be repeated
	*/
	public static function combine(Array $files, Array &$rendered );
	
	/**
	* debug() should return as a string, the unaltered filename in whatever wrapper is needed for displaying.
	* 
	* The {@link http://www.net-perspective.com/downloads/VP_JS VP_JS Javascript Plugin } returns: <br />
	* <code><script src="filename.js" type="text/javascript"></script></code>
	*/
	public function debug();
}

?>