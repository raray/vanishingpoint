<?php

/**
 * This is an example implementation of Vanishing Point using the VP_CSSS plugin. 
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
require_once('class.VP_CSS.php');

//create a Vanishing Point Object
$vp = new VanishingPoint();
//$vp->debugOn();

$vp->addModule(new VP_CSS("style.css","1-0-4",true),"test");
$vp->addModule(new VP_CSS("flash.css","3-2-2",true),"test");

VP_CSS::setHTPPPath("/Vanishing%20Point/styles/");
VP_CSS::setServerPath("styles/");

//print_r($vp);

print_r($vp->render("test"));

?>