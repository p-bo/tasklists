<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Tasklists plugin for GLPI
 Copyright (C) 2003-2016 by the Tasklists Development Team.

 https://github.com/InfotelGLPI/tasklists
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Tasklists.

 Tasklists is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Tasklists is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Tasklists. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * @return bool
 */
function plugin_tasklists_install() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/tasklists/inc/profile.class.php");
   include_once(GLPI_ROOT . "/plugins/tasklists/inc/task.class.php");
   if (!$DB->tableExists("glpi_plugin_tasklists_tasks")) {

      $DB->runFile(GLPI_ROOT . "/plugins/tasklists/sql/empty-1.3.0.sql");

   }

   PluginTasklistsProfile::initProfile();
   PluginTasklistsProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   return true;
}

/**
 * @return bool
 */
function plugin_tasklists_uninstall() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/tasklists/inc/profile.class.php");
   include_once(GLPI_ROOT . "/plugins/tasklists/inc/menu.class.php");

   $tables = ["glpi_plugin_tasklists_tasks",
                   "glpi_plugin_tasklists_tasktypes"];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   $tables_glpi = ["glpi_displaypreferences",
                        "glpi_notepads",
                        "glpi_savedsearches",
                        "glpi_logs"];

   foreach ($tables_glpi as $table_glpi) {
      $DB->query("DELETE FROM `$table_glpi` WHERE `itemtype` LIKE 'PluginTasklistsTask%';");
   }

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginTasklistsProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }
   PluginTasklistsMenu::removeRightsFromSession();

   PluginTasklistsProfile::removeRightsFromSession();

   return true;
}

// Define dropdown relations
/**
 * @return array
 */
function plugin_tasklists_getDatabaseRelations() {

   $plugin = new Plugin();

   if ($plugin->isActivated("tasklists")) {
      return ["glpi_plugin_tasklists_tasktypes" => ["glpi_plugin_tasklists_tasks" => "plugin_tasklists_tasktypes_id"],
                   "glpi_users"                      => ["glpi_plugin_tasklists_tasks" => "users_id"],
                   "glpi_groups"                     => ["glpi_plugin_tasklists_tasks" => "groups_id"],
                   "glpi_entities"                   => ["glpi_plugin_tasklists_tasks"     => "entities_id",
                                                              "glpi_plugin_tasklists_tasktypes" => "entities_id"]];
   } else {
      return [];
   }
}

// Define Dropdown tables to be manage in GLPI :
/**
 * @return array
 */
function plugin_tasklists_getDropdown() {

   $plugin = new Plugin();

   if ($plugin->isActivated("tasklists")) {
      return ['PluginTasklistsTaskType' => PluginTasklistsTaskType::getTypeName(2)];
   } else {
      return [];
   }
}

////// SEARCH FUNCTIONS ///////() {
/*
function plugin_tasklists_getAddSearchOptions($itemtype) {

   $sopt=array();

   if (in_array($itemtype, PluginTasklistsTask::getTypes(true))) {
      if (Session::haveRight("plugin_tasklists",READ)) {

         $sopt[4411]['table']='glpi_plugin_tasklists_tasktypes';
         $sopt[4411]['field']='name';
         $sopt[4411]['name']=PluginTasklistsTask::getTypeName(2)." - ".
                                      PluginTasklistsTaskType::getTypeName(1);
         $sopt[4411]['forcegroupby']=true;
         $sopt[4411]['datatype']       = 'dropdown';
         $sopt[4411]['massiveaction']  = false;
         $sopt[4411]['joinparams']     = array('beforejoin' => array(
                                                   array('table'      => 'glpi_plugin_tasklists_tasks',
                                                         'joinparams' => $sopt[4410]['joinparams'])));
      }
   }
   return $sopt;
}
*/
/**
 * @param $type
 * @param $ID
 * @param $data
 * @param $num
 *
 * @return string
 */
function plugin_tasklists_displayConfigItem($type, $ID, $data, $num) {

   $searchopt =& Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   switch ($table . '.' . $field) {
      case "glpi_plugin_tasklists_tasks.priority" :
         return " style=\"background-color:" . $_SESSION["glpipriority_" . $data[$num][0]['name']] . ";\" ";
         break;
   }
   return "";
}

/**
 * @param $options
 *
 * @return array
 */
function plugin_tasklists_getRuleActions($options) {
   $task = new PluginTasklistsTask();
   return $task->getActions();
}

/**
 * @param $options
 *
 * @return mixed
 */
function plugin_tasklists_getRuleCriterias($options) {
   $task = new PluginTasklistsTask();
   return $task->getCriterias();
}

/**
 * @param $options
 *
 * @return the
 */
function plugin_tasklists_executeActions($options) {
   $task = new PluginTasklistsTask();
   return $task->executeActions($options['action'], $options['output'], $options['params']);
}
