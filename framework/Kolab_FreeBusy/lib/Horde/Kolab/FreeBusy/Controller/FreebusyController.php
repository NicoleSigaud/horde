<?php
/**
 * The Kolab implementation of the free/busy system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Thomas Arendsen Hein <thomas@intevation.de>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * The core Controller handling the different request types.
 *
 * Copyright 2004-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Thomas Arendsen Hein <thomas@intevation.de>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 * @since    Horde 3.2
 */
class FreeBusyController extends Horde_Controller_Base
{
    /**
     * Parameters provided to this class.
     *
     * @var array
     */
    var $_params;

    /**
     * Link to the cache.
     *
     * @var Horde_Kolab_FreeBusy_Cache
     */
    var $_cache;

    /**
     * Setup the cache.
     */
    function _initCache()
    {
        global $conf;

        /* Load the cache class now */
        require_once 'Horde/Kolab/FreeBusy/Cache.php';

        /* Where is the cache data stored? */
        if (!empty($conf['fb']['cache_dir'])) {
            $cache_dir = $conf['fb']['cache_dir'];
        } else {
            if (class_exists('Horde')) {
                $cache_dir = Horde::getTempDir();
            } else {
                $cache_dir = '/tmp';
            }
        }

        $this->_cache = new Horde_Kolab_FreeBusy_Cache($cache_dir);
    }

    /**
     * Trigger regeneration of free/busy data in a calender.
     *
     * @return NULL
     */
    function &trigger()
    {
        global $conf;

        /* Get the folder name */
        $req_folder = Horde_Util::getFormData('folder', '');

        Horde::logMessage(sprintf("Starting generation of partial free/busy data for folder %s",
                                  $req_folder), __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Validate folder access */
        $access = new Horde_Kolab_FreeBusy_Access();
        $result = $access->parseFolder($req_folder);
        if (is_a($result, 'PEAR_Error')) {
            $error = array('type' => FREEBUSY_ERROR_NOTFOUND,
                           'error' => $result);
            $view = new Horde_Kolab_FreeBusy_View_error($error);
            return $view;
        }

        Horde::logMessage(sprintf("Partial free/busy data of owner %s on server %s requested by user %s.",
                                  $access->owner, $access->freebusyserver, $access->user),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Get the cache request variables */
        $req_cache    = Horde_Util::getFormData('cache', false);
        $req_extended = Horde_Util::getFormData('extended', false);

        /* Try to fetch the data if it is stored on a remote server */
        $result = $access->fetchRemote(true, $req_extended);
        if (is_a($result, 'PEAR_Error')) {
            $error = array('type' => FREEBUSY_ERROR_UNAUTHORIZED,
                           'error' => $result);
            $view = new Horde_Kolab_FreeBusy_View_error($error);
            return $view;
        }

        $this->_initCache();

        if (!$req_cache) {
            /* User wants to regenerate the cache */

            /* Here we really need an authenticated IMAP user */
            $result = $access->authenticated();
            if (is_a($result, 'PEAR_Error')) {
                $error = array('type' => FREEBUSY_ERROR_UNAUTHORIZED,
                               'error' => $result);
                $view = new Horde_Kolab_FreeBusy_View_error($error);
                return $view;
            }

            if (empty($access->owner)) {
                $message = sprintf(_("No such account %s!"),
                                   htmlentities($access->req_owner));
                $error = array('type' => FREEBUSY_ERROR_NOTFOUND,
                               'error' => PEAR::raiseError($message));
                $view = new Horde_Kolab_FreeBusy_View_error($error);
                return $view;
            }

            /* Update the cache */
            $result = $this->_cache->store($access);
            if (is_a($result, 'PEAR_Error')) {
                $error = array('type' => FREEBUSY_ERROR_NOTFOUND,
                               'error' => $result);
                $view = new Horde_Kolab_FreeBusy_View_error($error);
                return $view;
            }
        }

        /* Load the cache data */
        $vfb = $this->_cache->loadPartial($access, $req_extended);
        if (is_a($vfb, 'PEAR_Error')) {
            $error = array('type' => FREEBUSY_ERROR_NOTFOUND,
                           'error' => $vfb);
            $view = new Horde_Kolab_FreeBusy_View_error($error);
            return $view;
        }

        Horde::logMessage("Delivering partial free/busy data.", __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Generate the renderer */
        $data = array('fb' => $vfb, 'name' => $access->owner . '.ifb');
        $view = new Horde_Kolab_FreeBusy_View_vfb($data);

        /* Finish up */
        Horde::logMessage("Free/busy generation complete.", __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $view;
    }

    /**
     * Fetch the free/busy data for a user.
     *
     * @return NULL
     */
    function &fetch()
    {
        global $conf;

        /* Get the user requsted */
        $req_owner = Horde_Util::getFormData('uid');

        Horde::logMessage(sprintf("Starting generation of free/busy data for user %s",
                                  $req_owner), __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Validate folder access */
        $access = new Horde_Kolab_FreeBusy_Access();
        $result = $access->parseOwner($req_owner);
        if (is_a($result, 'PEAR_Error')) {
            $error = array('type' => FREEBUSY_ERROR_NOTFOUND, 'error' => $result);
            $view = new Horde_Kolab_FreeBusy_View_error($error);
            return $view;
        }

        Horde::logMessage(sprintf("Free/busy data of owner %s on server %s requested by user %s.",
                                  $access->owner, $access->freebusyserver, $access->user),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $req_extended = Horde_Util::getFormData('extended', false);

        /* Try to fetch the data if it is stored on a remote server */
        $result = $access->fetchRemote(false, $req_extended);
        if (is_a($result, 'PEAR_Error')) {
            $error = array('type' => FREEBUSY_ERROR_UNAUTHORIZED, 'error' => $result);
            $view = new Horde_Kolab_FreeBusy_View_error($error);
            return $view;
        }

        $this->_initCache();

        $result = $this->_cache->load($access, $req_extended);
        if (is_a($result, 'PEAR_Error')) {
            $error = array('type' => FREEBUSY_ERROR_NOTFOUND, 'error' => $result);
            $view = new Horde_Kolab_FreeBusy_View_error($error);
            return $view;
        }

        Horde::logMessage("Delivering complete free/busy data.", __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Generate the renderer */
        $data = array('fb' => $result, 'name' => $access->owner . '.vfb');
        $view = new Horde_Kolab_FreeBusy_View_vfb($data);

        /* Finish up */
        Horde::logMessage("Free/busy generation complete.", __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $view;
    }

    /**
     * Regenerate the free/busy cache data.
     *
     * @return NULL
     */
    function &regenerate($reporter)
    {
        $access = new Horde_Kolab_FreeBusy_Access();
        $result = $access->authenticated();
        if (is_a($result, 'PEAR_Error')) {
            return $result->getMessage();
        }

        /* Load the required Kolab libraries */
        require_once "Horde/Kolab/Storage/List.php";

        $list = &Kolab_List::singleton();
        $calendars = $list->getByType('event');
        if (is_a($calendars, 'PEAR_Error')) {
            return $calendars->getMessage();
        }

        $this->_initCache();

        $lines = array();

        foreach ($calendars as $calendar) {
            /**
             * We are using imap folders for our calendar list but
             * the library expects us to follow the trigger format
             * used by pfb.php
             */
            $req_domain = explode('@', $calendar->name);
            if (isset($req_domain[1])) {
                $domain = $req_domain[1];
            } else {
                $domain = null;
            }
            $req_folder = explode('/', $req_domain[0]);
            if ($req_folder[0] == 'user') {
                unset($req_folder[0]);
                $owner = $req_folder[1];
                unset($req_folder[1]);
            } else if ($req_folder[0] == 'INBOX') {
                $owner = $access->user;
                unset($req_folder[0]);
            }

            $trigger = $owner . ($domain ? '@' . $domain : '') . '/' . join('/', $req_folder);
            $trigger = Horde_String::convertCharset($trigger, 'UTF7-IMAP', 'UTF-8');

            /* Validate folder access */
            $result = $access->parseFolder($trigger);
            if (is_a($result, 'PEAR_Error')) {
                $reporter->failure($calendar->name, $result->getMessage());
                continue;
            }

            /* Hack for allowing manager access */
            if ($access->user == 'manager') {
                $imapc = &Horde_Kolab_IMAP::singleton($GLOBALS['conf']['kolab']['imap']['server'],
                                                      $GLOBALS['conf']['kolab']['imap']['port']);
                $result = $imapc->connect($access->user, Horde_Auth::getCredential('password'));
                if (is_a($result, 'PEAR_Error')) {
                    $reporter->failure($calendar->name, $result->getMessage());
                    continue;
                }
                $acl = $imapc->getACL($calendar->name);
                if (is_a($acl, 'PEAR_Error')) {
                    $reporter->failure($calendar->name, $result->getMessage());
                    continue;
                }
                $oldacl = '';
                if (isset($acl['manager'])) {
                    $oldacl = $acl['manager'];
                }
                $result = $imapc->setACL($calendar->name, 'manager', 'lrs');
                if (is_a($result, 'PEAR_Error')) {
                    $reporter->failure($calendar->name, $result->getMessage());
                    continue;
                }
            }

            /* Update the cache */
            $result = $this->_cache->store($access);
            if (is_a($result, 'PEAR_Error')) {
                $reporter->failure($calendar->name, $result->getMessage());
                continue;
            }

            /* Revert the acl  */
            if ($access->user == 'manager' && $oldacl) {
                $result = $imapc->setACL($calendar->name, 'manager', $oldacl);
                if (is_a($result, 'PEAR_Error')) {
                    $reporter->failure($calendar->name, $result->getMessage());
                    continue;
                }
            }

            $reporter->success($calendar->name);

        }
        return $lines;
    }

    /**
     * Delete data for a specific user.
     *
     * @return NULL
     */
    public function delete()
    {
    }
}