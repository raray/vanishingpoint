<?php
/**
 * This is an example implementation of Vanishing Point using the VP_JS plugin. 
 *
 * @link http://www.net-perspective.com/ Net Perspective Website
 * 
 * @version 1.0.0
 * @author Andrew Ellis <aellis@net-perspective.com>
 * @copyright Copyright (c) 2008, Net Perspective, LLC
 * @license http://www.opensource.org/licenses/mit-license.php MIT License 
 * 
 * @package VanishingPoint
 * @subpackage Examples
 */

//include the Vanishing Point framework
require_once('class.VanishingPoint.php');
//include the desired plugin
require_once('class.VP_JS.php');

//create a Vanishing Point Object
$vp = new VanishingPoint();

//The following is how to add modules to Vanishing Point, each module 
//added requires a VP_Module object (which will have it's own set of parameters)
//as well as a string (the group) that the module is to be added to.
//Below are examples of various ways to use addModule() using the VP_JS plugin.

//and place it into the group 'test'
//Here we add init.js, with the version of 1.0.0, mode 7 (pack, move, and version), and true for combination
$vp->addModule(new VP_JS("scripts/init.js","1-0-0",7,true),"test");

//Here we again place meun.js into 'test', however; instead of using an integer to declare mode, we used
//VP_JS's built in constants (PACK, VERSION, and MOVE). By using a bitwise OR ( the pipe: |) we can combine them.
//This allows for both flexibility as well as readability. We also omitted the 4th parameter (combine), and allowed
//the default value of true to be used.
$vp->addModule(new VP_JS("scripts/menu.js","1-0-0",VP_JS::PACK|VP_JS::VERSION|VP_JS::MOVE),"test");

//Here we have shown that despite a true being passed in for combination, combination is only done on files
//that are in mode 7, meaning they can be packed, moved, and versioned.
$vp->addModule(new VP_JS("scripts/display.js","1-0-0",4,true),"test");

//Here we have created a 2nd group, example, with 2 files. Both are set to combine with mode 7, so this is a go.
$vp->addModule(new VP_JS("scripts/header.js","1-0-0",7,true),"example");
$vp->addModule(new VP_JS("scripts/scroll.js","1-0-0",7,true),"example");

//To render all of the modules loaded into Vanishin Point, a class constant is provided.
$output = $vp->render(VanishingPoint::RENDER_ALL);

//Here we can see that you can render ONLY the groups you want. This allows you to build
//a large configuration, but only server relevant JS (or other files depending on your plugins.)
//These lines are commented so as to not interfere with the previous render.
//$output = $vp->render("test");
//$output = $vp->render("example");

//diagnostic output for easy viewing. (Be sure to 'view-source', as the javascript includes won't be visable.)
print_r($output);

?>