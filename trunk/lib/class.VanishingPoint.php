<?php

/**
 * Vanishing Point Optimizer Class
 * 
 * Vanishing Point is a framework that enables the programmer to implement plugins for 
 * different file types to be optimized. The class's main role in optimization is managing the files and calling the 
 * appropriate render(). Plugins are based upon the VP_Module interface and should 
 * adhere to the standards defined in the VP_Module documentation. 
 * 
 * Vanishing Point accepts files through addModule() which takes:
 * <ol>
 * <li>An object implementing the VP_Module interface</li>
 * <li>A 'group' to place this object into</li>
 * </ol>
 *  These 'groups' allow logical groupings of files to be designated. For example, a javascript 
 * group may be defined to deal with all AJAX requests. Mixed with render()'s ability to output only selected 'groups', the 
 * programmer can logically build dependancies and output the AJAX javascript includes only when AJAX is going to be required.
 * 
 * This class is NOT compatible with PHP 4.
 * 
 * The following is an example of using Vanishing Point. For the sake of demonstration, we have used 
 * the VP_JS Javascript Plugin for 
 * Vanishing Point ({@link http://www.net-perspective.com/downloads/VP_JS VP_JS Javascript Plugin })
 * <code>
 * <?php
 * //include requied classes
 * require_once('class.VanishingPoint.php');
 * require_once('class.VP_JS.php');
 * 
 * //create the new Vanishing Point object
 * $vp = new VanishingPoint();
 * 
 * //add a module of type VP_JS to the group 'example' with version 1-0-0
 * $vp->addModule(new VP_JS("example.js","1-0-0"),"example");
 * 
 * $output = $vp->render("example"); 
 * 
 * echo $output['example'];
 * ?>
 * </code>
 * 
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
class VanishingPoint
{	
	private $modules = array();
	private $rendered = array();
	private $debug = false;
	
	/**
	* Used to render all modules, called by:
	* <code>
	* <?php
	* $vp->render(VanishingPoint::RENDER_ALL);
	* ?>
	* </code>
	*/
	const RENDER_ALL = 1;
	
	public function __construct(  )	
	{
		//This line of code is not to be removed. 
		//echo "<!-- Optimized by Vanishing Point: http://www.net-perspective.com/downloads/vanishing_point -->\n";
	}
	
	/**
	* debugOn() sets Vanishing Point's mode to debug. 
	* 
	* In debug mode, the file should be returned as is, no modifications are 
	*made to the file and the returned output from render() should be the path name specified at configuration.
	*/
	public function debugOn()
	{
		$this->debug = true;
	}
	
	/**
	* debugOff() returns Vanishing Point to normal operations
	*/
	public function debugOff()
	{
		$this->debug = false;
	}
	
	/**
	 * addModule() allows for a module implementing the VP_Module interface to be added to a group. 
	 * 
	 * The groups are logical representations for the programmer to use to rendere commmon files together.
	 * 
	 * @param VP_Module $mod The module to be added. Must be a class implementing VP_Module
	 * @param string $group The group to add the module to.
	 */
	public function addModule( VP_Module $mod, $group = 'default')	
	{
		//add the VP_module to it's group
		//if the group exists it will be added to it, if not the group is created
		$this->modules[strtolower($group)][] = $mod;		
	}
	
	/**
	 * render() accepts either a string or the class constant RENDER_ALL.
	 * 
	 * When a string is passed to render() 
	 * the associated group with that name will be rendered. If RENDER_ALL is passed to render() all groups
	 * will be rendered.
	 * 
	 * When Vanishing Point's render() function is called, the array of files is iterated through and each file 
	 * is run through its module's render(), combine(), or debug() function based on the current Vanishing Point mode 
	 * and the options provided to the module.
	 * 
 	 * The returned value or render() is a keyed array of HTML, paths, or any other logical output to be displayed. The keys 
	 * for each set of values are the module names. 
	 * 
	 * Exmaple return (using the {@link http://www.net-perspective.com/downloads/VP_JS VP_JS Javascript Plugin }):
	 * <code>
	 * Array(
	 * 'module1' => "<script type=\"text/javascript\" src=\"fileNameHere\"></script>"
	 *)
	 *</code>
	 * 
	 * @param string $module A module to render
	 * @return array
	 */
	public function render($module = "default")	
	{
		//check to make sure the module exists
		if(!isset($this->modules[strtolower($module)]) && $module != self::RENDER_ALL)
		{
			throw new Exception("Module " . $module . " does not exist");
		}

		$output = array();		
		//output ALL files
		//the RENDER_ALL code is nearly identical to the single module code, for comments please see that portion of the code.
		if($module === self::RENDER_ALL)
		{
			$keys = array_keys($this->modules);
			foreach($keys as $key)
			{
				$output[$key] = "";
				if(!$this->debug)
				{
					reset($this->modules[$key]);
					$tmp = get_class(current($this->modules[$key]));	
					
					$output[$key] = call_user_func_array( array(new $tmp(null,null,null),'combine'), array($this->modules[$key],&$this->rendered) );
				}
				foreach($this->modules[$key] as $m)
				{
					$name = $m->getFileName();		
					if($this->debug && !in_array($name, $this->rendered))
					{
						$output[$key] .= $m->debug();					
						$this->rendered[] = $name;
					}
					else if(!in_array($name, $this->rendered))
					{
						$output[$key] .= $m->render();					
						$this->rendered[] = $name;
					}
				}				
			}
		}
		//output a specific group of files
		else
		{
			$output[strtolower($module)] = "";
			if(!$this->debug)
			{
				//if not in debug mode, reset the array, get the first item, find it's class, and call that class's combine function
				// on all other files in that group
				reset($this->modules[strtolower($module)]);
				$tmp = get_class(current($this->modules[strtolower($module)]));									
				$output[strtolower($module)] = call_user_func_array( array(new $tmp(null,null,null),'combine'), array($this->modules[strtolower($module)],&$this->rendered) );
			}
			foreach($this->modules[strtolower($module)] as $m)
			{
				//get the name of the file
				$name = $m->getFileName();						
				if($this->debug && !in_array($name, $this->rendered))
				{
					//if we are in debug mode and the file has not been operated on previously,
					//this prevents duplicates, and call debug()
					$output[strtolower($module)] .= $m->debug();		
					//add the name to the rendered list
					$this->rendered[] = $name;
				}
				else if(!in_array($name, $this->rendered))
				{
					//if we are not in debug mode and the file has not been worked on previously 
					// the render() function is called
					$output[strtolower($module)] .= $m->render();	
					//add the name to the rendered list
					$this->rendered[] = $name;
				}
			}
		}	
		//return the output
		return $output;
	}	
}
?>