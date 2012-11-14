<?php
/***************************************************************************
 * Xlrstats Webmodule
 * Webfront for XLRstats for B3 (www.bigbrotherbot.com)
 * (c) 2004-2010 www.xlr8or.com (mailto:xlr8or@xlr8or.com)
 ***************************************************************************/

/***************************************************************************
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 *  http://www.gnu.org/copyleft/gpl.html
 ***************************************************************************/

// no direct access
defined( '_XLREXEC' ) or die( 'Restricted access' );

function install_award_idents()
{
  //scan available configs and save the appropriate awardfiles
  configscanner();
  end_process();

  //delete cache to display new medal owners
  $files = glob("dynamic/cache/*.txt");
  foreach($files as $file) 
  {
    unlink($file);
  }
}

//********************************************************************************
//  FUNCTIONS
//********************************************************************************

function identify_config()
{
  global $currentconfignumber;
  global $currentconfig;
  global $cpath;
  // If statsconfig.php exists, we won't enable multiconfig functionality
  if (file_exists($cpath."config/statsconfig.php"))
  {
    $currentconfig = "statsconfig.php";
    $currentconfignumber = 0;
  }
  elseif (file_exists($cpath."config/statsconfig1.php"))
  {
    $currentconfig = "statsconfig1.php";
    $currentconfignumber = 1;
    // Was a config set in the url?
    if (isset($_GET['config'])) 
    {
      $currentconfignumber = escape_string($_GET['config']);
      $currentconfig = "statsconfig".$currentconfignumber.".php";
      $_SESSION['currentconfignumber'] = $currentconfignumber;
    }
    if (isset($_SESSION['currentconfignumber']))
    {
      $currentconfignumber = $_SESSION['currentconfignumber'];
      $currentconfig = "statsconfig".$currentconfignumber.".php";
    }
  }
}

function identify_function()
{
  global $func;

  if (isset($_GET['func']))
    $func = escape_string($_GET['func']);
}

function configscanner()
{
  global $currentconfig;
  global $currentconfignumber;
  global $db_host;
  global $db_user;
  global $db_pass;
  global $db_db;
  global $coddb;
  global $filename;
  global $buffer;
  global $t;
  global $cpath;

  $c = true;
  $cnt = 0;
  //$configlist[]= "";
  while ($c == true)
  {
    $cnt++;
    $filename = $cpath."config/statsconfig".$cnt.".php";
    if (file_exists($filename)) $configlist[] = $cnt;
    else $c = false;
  }
  if ($cnt > 2)
  {
    foreach  ($configlist as $value)
    {
      $currentconfignumber = $value;
      $config = $cpath."config/statsconfig".$value.".php";
      include($config);
      echo "<p class=\"fontTitle\">Reading configfile nr. ".$value." (for game: ".$game.")</p><br />";
      startbuffer();
      $tfunc = $game."_awards();";
      eval($tfunc);
      closebuffer_write();
      unset($tfunc);
    }
  }
  else
  {
    $currentconfignumber = 0;
    $config = $cpath."config/statsconfig.php";
    include($config);
    echo "<p class=\"fontTitle\">Reading configfile (for game: ".$game.")</p><br />";
    startbuffer();
    $tfunc = $game."_awards();";
    eval($tfunc);
    closebuffer_write();
    unset($tfunc);
  }
}

function startbuffer()
{
  global $currentconfig;
  global $currentconfignumber;
  global $db_host;
  global $db_user;
  global $db_pass;
  global $db_db;
  global $coddb;
  global $filename;
  global $buffer;
  global $cpath;
  // Open the file
  $buffer = "<?php\n";
  
  $buffer .= "//------------------------------------------------------\n";
  $buffer .= "// This is an automatically generated file!\n";
  $buffer .= "// Do not alter this unless you know what you are doing!\n";
  $buffer .= "//------------------------------------------------------\n\n";
  $buffer .= "// no direct access\n";
  $buffer .= "defined( '_XLREXEC' ) or die( 'Restricted access' );\n";

  
  $coddb = new sql_db($db_host, $db_user, $db_pass, $db_db, false);
  if(!$coddb->db_connect_id) 
  {
      die('<p class="attention">Could not connect to the database!<br />Did you setup this statsconfig file ('.$currentconfig.') correctly?</p></body></html>');
  }
  
  if ($currentconfignumber == 0)
    $filename = $cpath."dynamic/award_idents.php";
  else
    $filename = $cpath."dynamic/award_idents_$currentconfignumber.php";
  
  if (!file_exists($filename))
  {
    touch($filename);
    if (!file_exists($filename))
      die('<p class="attention">Could not create the configfile. Make sure your config directory is writable!</p></body></html>');
  }
  
  if (!is_writable($filename))
    die('<p class="attention">The file is not writable</p></body></html>');
  echo "<span class=\"precheckOK\">...writing ".$filename."</span><br /><br />";
}

function closebuffer_write()
{
  global $coddb;
  global $buffer;
  global $filename;

  $buffer .= "?".">\n";
  file_put_contents($filename, $buffer);
  $coddb->sql_close();
}

function end_process()
{
  global $cpath;
  echo "<p class=\"precheckOK\"><strong>Your awards have been identified using the current database content.</strong></p>";
  echo "<p class=\"fontNormal\">1.) You may run \"http://www.yoursite.com/xlrstats/?func=cron\" at any time if you feel that certain awards are not good or certain weapons have only recently been used for the first time.<br />"; 
  //echo "Bookmark current URL to rerun this file later.</i></p>";
  echo "<p class=\"attention\">2.) When you're sure all awards are correct and all weapons have been used, delete/move the install directory so it can no longer be called directly.)</p>";
  echo "<p class=\"fontNormal\">Click \"Stats Home\" button to return to the frontpage</p>";
  echo "<p class=\"fontNormal\"><a href=\"http://www.xlr8or.com/\">(made at www.xlr8or.com)</a></p>";
  echo "<tr>";
  echo "<td valign=\"top\"><table width=\"100%\" border=\"0\" cellspacing=\"5\" cellpadding=\"5\">";
  echo "<tr>";
  echo "<td>&nbsp;</td>";
  echo "<td width=\"80\" align=\"center\" valign=\"middle\"><a href=\"".$cpath."index.php\">";
  echo "<label>";
  echo "<input name=\"Next\" type=\"button\" class=\"line1\" id=\"Next\" value=\"Stats Home\" />";
  echo "</label>";
  echo "</a></td>";
  echo "</tr>";
  echo "</table>";
}

function add_weaponaward($var, $weaps)
{
  global $t;
  global $coddb;
  global $buffer;

  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name IN ($weaps)
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$".$var." = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

}

function add_bodypartaward($var, $bodyp)
{
  global $t;
  global $coddb;
  global $buffer;

  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name IN ($bodyp)
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$".$var." = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

//********************************************************************************
//  AWARDS
//********************************************************************************


function cod1_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";
  
  // Bashes
  add_weaponaward("wp_bashes", "'mod_melee'"); 
  // Nades
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE '%frag%'
            OR name LIKE '%granate%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_nades = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  // Snipers
  add_weaponaward("wp_snipers", "'springfield_mp', 'kar98k_sniper_mp', 'mosin_nagant_sniper_mp', 'enfield_scope_mp'"); 
  // Pistols
  add_weaponaward("wp_pistols", "'colt_mp', 'luger_mp', 'webley_mp', 'TT30_mp'"); 
  // Accidents
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name IN ('none', 'mod_falling')
            OR name LIKE '%frag%'
            OR name LIKE '%granate%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_accidents = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head 
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name = 'head'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function coduo_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";
  
  // bomb (satchell)
  add_weaponaward("wp_bomb", "'satchelcharge_mp'"); 
  // Bashes
  add_weaponaward("wp_bashes", "'mod_melee'"); 
  // Nades
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE '%frag%'
            OR name LIKE '%granate%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_nades = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  // Snipers
  add_weaponaward("wp_snipers", "'springfield_mp', 'kar98k_sniper_mp', 'mosin_nagant_sniper_mp', 'enfield_scope_mp'"); 
  // Pistols
  add_weaponaward("wp_pistols", "'colt_mp', 'luger_mp', 'webley_mp', 'tt33_mp'"); 
  // Accidents
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name IN ('none', 'binoculars_artillery_mp')
            OR name LIKE '%frag%'
            OR name LIKE '%granate%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_accidents = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head 
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name = 'head'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function cod2_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";
  
  // Bashes
  add_weaponaward("wp_bashes", "'mod_melee'"); 
  // Nades
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE '%frag_grenade%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_nades = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  // Snipers
  add_weaponaward("wp_snipers", "'springfield_mp', 'kar98k_sniper_mp', 'mosin_nagant_sniper_mp', 'enfield_scope_mp'"); 
  // Pistols
  add_weaponaward("wp_pistols", "'colt_mp', 'luger_mp', 'webley_mp', 'TT30_mp'"); 
  // Accidents
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name = 'none'
            OR name LIKE '%frag_grenade%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_accidents = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head 
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name = 'head'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function cod4_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";
  
  // Claymore
  add_weaponaward("wp_claymore", "'claymore_mp'"); 
  // Fireman (Car)
  add_weaponaward("wp_fireman", "'destructible_car'"); 
  // bomb (C4)
  add_weaponaward("wp_bomb", "'c4_mp'"); 
  // Knives
  add_weaponaward("wp_knives", "'mod_melee'"); 
  // Nades
  add_weaponaward("wp_nades", "'frag_grenade_mp', 'frag_grenade_short_mp'"); 
  // Snipers
  add_weaponaward("wp_snipers", "'m40a3_acog_mp', 'm40a3_mp', 'm21_acog_mp', 'm21_mp','dragunov_mp', 'dragunov_acog_mp', 'remington700_mp', 'remington700_acog_mp', 'humvee_50cal_mp'"); 
  // Pistols
  add_weaponaward("wp_pistols", "'colt45_mp', 'colt45_silencer_mp', 'usp_mp', 'usp_silencer_mp', 'beretta_mp', 'beretta_silencer_mp', 'deserteagle_mp', 'deserteaglegold_mp'"); 
  // Accidents
  add_weaponaward("wp_accidents", "'artillery_mp', 'mod_falling', 'none'"); 
  
  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head 
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name = 'head'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function codwaw_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";
  
  // Bouncing Betty
  add_weaponaward("wp_bouncingbetty", "'mine_bouncing_betty_mp'"); 
  // Molotov
  add_weaponaward("wp_molotov", "'molotov_mp'"); 
  // Flame Thrower
  add_weaponaward("wp_flamethrower", "'m2_flamethrower_mp'"); 
  // Bashes
  add_weaponaward("wp_knives", "'mod_melee'"); 
  // Nades
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE ('%frag_grenade%')
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_nades = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  // Snipers
  add_weaponaward("wp_snipers", "'springfield_scoped_mp', 'kar98k_scoped_mp', 'm1garand_scoped_mp', 'mosinrifle_scoped_mp', 'type99rifle_scoped_mp'"); 
  // Pistols
  add_weaponaward("wp_pistols", "'colt_mp', 'luger_mp', 'webley_mp', 'TT30_mp'"); 
  // Fireman (Car)
  add_weaponaward("wp_fireman", "'destructible_car'"); 
  // Accidents
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name = 'none'
            OR name LIKE '%frag_grenade%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_accidents = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  // Barrel Victim
  add_weaponaward("wp_barrel", "'explodable_barrel'"); 

  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head 
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name = 'head'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function urt_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";
  
  // Knives
  add_weaponaward("wp_knives", "'12', '13'"); 
  // Nades
  add_weaponaward("wp_nades", "'22', '25', '37'"); 
  // Snipers
  add_weaponaward("wp_snipers", "'21', '28'"); 
  // Pistols
  add_weaponaward("wp_pistols", "'14', '15'"); 
  // Accidents
  add_weaponaward("wp_accidents", "'1', '6', 'mod_falling', '7', '31'"); 
  
  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head and Helmet
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name IN ('0', '1')
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function wop_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";
  
  // Knives - Punchy
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name = 2
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_punchy = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  // Nades - Ballooney
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name IN ('4', '5')
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_ballooney = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  // Snipers - Betty
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name IN ('6', '7')
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_betty = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  // Pistols - Killerducks
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name = 14
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_killerducks = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  // Accidents
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name IN ('15', '16', '17', '18', '19', 'mod_falling', '20',)
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_accidents = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function smg_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";
  
  // Not used by UrT
  //$buffer .= "\$wp_bomb = 0;\n";
  //$buffer .= "\$wp_fireman = 0;\n";
  //$buffer .= "\$wp_claymore = 0;\n";
  
  // Knives
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name = '1'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_knives = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  // Dynamit
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name = '12'
            LIMIT 0 , 30";

  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);

  $buffer .= "\$wp_dynamite = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

  // Pistols
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name IN ('2', '3', '4')
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_pistols = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

  // Molotov
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name = '13'
            LIMIT 0 , 30";

  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);

  $buffer .= "\$wp_molotov = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

  // Accidents
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name IN ('20', '22', 'mod_falling')
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_accidents = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
  
  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name = 'head'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function bfbc2_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";

  // Snipers
  add_weaponaward("wp_snipers", "'M24', 'M95', 'GOL', 'QBU88', 'SV98', 'VSS'"); 
  // Pistols
  add_weaponaward("wp_pistols", "'M1911', 'MP443', '9', 'M9-3'"); 
  // Nades
  add_weaponaward("wp_nades", "'HG-2'"); 
  // Knives
  add_weaponaward("wp_knives", "'knv-1'"); 
  // Accidents
  add_weaponaward("wp_accidents", "'roadkill', 'RPG7', 'DTN-4', 'MRTR-5', 'M2CG', 'M136', 'TM-00', '40mmgl'"); 

  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head
  add_bodypartaward("bp_head", "'head'"); 
}

function moh_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";

  // Snipers
  add_weaponaward("wp_snipers", "'H_M21', 'H_M24', 'H_G3', 'H_SV98', 'H_SVD'");
  // Pistols
  add_weaponaward("wp_pistols", "'H_M9', 'H_TARIQ'");
  // Nades
  add_weaponaward("wp_nades", "'H_HG', 'H_SHG'");
  // bomb (C4)
  add_weaponaward("wp_bomb", "'H_C4', 'H_IED'");
  // Knives
  add_weaponaward("wp_knives", "'H_AXE', 'H_KNIFE'");
  // Accidents
  add_weaponaward("wp_accidents", "'H_ROADKILL', 'KILL_MSG_SUICIDE'"); 

  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head
  add_bodypartaward("bp_head", "'head'");
}

function cod6_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";

  // Nades
  add_weaponaward("wp_nades", "'frag_grenade_mp', 'frag_grenade_short_mp', 'semtex_mp', 'flash_grenade_mp', 'smoke_grenade_mp', 'concussion_grenade_mp'"); 

  // Snipers
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE 'cheytac_%'
            OR name LIKE 'barrett_%'
            OR name LIKE 'wa2000_%'
            OR name LIKE 'm21_%'
            LIMIT 0 , 100";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_snipers = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

  // Pistols
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE 'usp_%'
            OR name LIKE 'coltanaconda_%'
            OR name LIKE 'deserteagle%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_pistols = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

  // bomb (C4)
  add_weaponaward("wp_bomb", "'c4_mp'"); 
  // Claymore
  add_weaponaward("wp_claymore", "'claymore_mp'"); 
  // Knives
  add_weaponaward("wp_knives", "'throwingknife_mp', 'mod_melee'"); 
  // Fireman (Car)
  add_weaponaward("wp_fireman", "'destructible_car'"); 
  // Barrel Victim
  add_weaponaward("wp_barrel", "'barrel_mp'"); 
  // Accidents
  add_weaponaward("wp_accidents", "'briefcase_bomb_mp', 'scavenger_bag_mp', 'mod_falling', 'none'"); 
  
  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head 
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name = 'head'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function cod7_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";

  // Nades
  add_weaponaward("wp_nades", "'frag_grenade_mp', 'sticky_grenade_mp'"); 

  // Snipers
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE 'dragunov_%'
            OR name LIKE 'wa2000_%'
            OR name LIKE 'l96a1_%'
            OR name LIKE 'psg1_%'
            LIMIT 0 , 100";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_snipers = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

  // Pistols
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE 'asp%'
            OR name LIKE 'm1911%'
            OR name LIKE 'makarov%'
            OR name LIKE 'python%'
            OR name LIKE 'cz75%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_pistols = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

  // Flame Thrower
  $query = "SELECT id 
            FROM ${t["weapons"]}
            WHERE name LIKE 'ft_%'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$wp_flamethrower = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);

  //hatchet (Thomahawk)
  add_weaponaward("wp_hatchet", "'hatchet_mp'");
  //Crossbow
  add_weaponaward("wp_crossbow", "'crossbow_explosive_mp', 'crossbow_mp', 'explosive_bolt_mp'");
  // bomb (C4)
  add_weaponaward("wp_bomb", "'satchel_charge_mp'"); 
  // Claymore
  add_weaponaward("wp_claymore", "'claymore_mp'"); 
  // Knives
  add_weaponaward("wp_knives", "'mod_melee'"); 
  // Fireman (Car)
  add_weaponaward("wp_fireman", "'destructible_car_mp'"); 
  // Barrel Victim
  add_weaponaward("wp_barrel", "'explodable_barrel_mp'"); 
  // Accidents
  add_weaponaward("wp_accidents", "'briefcase_bomb_mp', 'mod_falling', 'none'"); 
  
  //-- Bodyparts -----------------------------------------------------------------
  $buffer .= "\n// Bodyparts ---------------------\n";
  
  // Head 
  $query = "SELECT id 
            FROM ${t["bodyparts"]}
            WHERE name = 'head'
            LIMIT 0 , 30";
  
  $result = $coddb->sql_query($query);
  $numrows = $coddb->sql_numrows($result);
  
  $buffer .= "\$bp_head = \"(";
  $c = 0;
  while ($row = $coddb->sql_fetchrow($result))
  {
    $c += 1;
    $buffer .= $row["id"];
    if($c < $numrows)
      $buffer .=  ", ";
  }
  $buffer .= ")\";\n";
  $coddb->sql_freeresult($result);
}

function homefront_awards()
{}

function bf3_awards()
{
  global $t;
  global $coddb;
  global $buffer;
  
  //-- Weapons -------------------------------------------------------------------
  $buffer .= "\n// Weapons / Means of Death --------\n";

  // Nades
  add_weaponaward("wp_nades", "'M67'");
  // Snipers
  add_weaponaward("wp_snipers", "'SV98', 'SKS', 'M40A5', 'Model98B', 'Mk11', 'SVD', 'M39'");
  // Head
  add_bodypartaward("bp_head", "'head'");
  // Pistol
  add_weaponaward("wp_pistols", "'M1911', 'M9', 'Weapons/MP443/MP443', 'Glock18', 'Taurus .44', 'Weapons/MP412Rex/MP412REX', 'M93R'");
  // bomb (C4)
  add_weaponaward("wp_bomb", "'Weapons/Gadgets/C4/C4'");
  // Claymore
  add_weaponaward("wp_claymore", "'Weapons/Gadgets/Claymore/Claymor'");
  // Knives
  add_weaponaward("wp_knives", "'Weapons/Knife/Knife', 'Melee'");
  // Accidents
  add_weaponaward("wp_accidents", "'Suicide', 'RoadKill', 'SoldierCollision'"); 
}

function ravaged_awards()
{}
?>