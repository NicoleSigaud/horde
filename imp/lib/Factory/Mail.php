<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for IMP's configuration of Horde_Mail.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Mail extends Horde_Core_Factory_Injector
{
    /**
     * Debug stream.
     *
     * @var resource
     */
    private $_debug;

    /**
     * Return the Horde_Mail instance.
     *
     * @return Horde_Mail  The singleton instance.
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $params = $GLOBALS['session']->get('imp', 'smtp', Horde_Session::TYPE_ARRAY);

        /* If SMTP authentication has been requested, use either the username
         * and password provided in the configuration or populate the username
         * and password fields based on the current values for the user. Note
         * that we assume that the username and password values from the
         * current IMAP / POP3 connection are valid for SMTP authentication as
         * well. */
        if (!empty($params['auth'])) {
            $imap_ob = $injector->getInstance('IMP_Imap');
            if (empty($params['username'])) {
                $params['username'] = $imap_ob->getParam('username');
            }
            if (empty($params['password'])) {
                $params['password'] = $imap_ob->getParam('password');
            }
        }

        if (!empty($params['debug'])) {
            $this->_debug = fopen($params['debug'], 'a');
            stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
            stream_filter_append($this->_debug, 'horde_eol', STREAM_FILTER_WRITE, array(
                'eol' => "\n"
            ));

            unset($params['debug']);
        }

        $class = $this->_getDriverName($GLOBALS['conf']['mailer']['type'], 'Horde_Mail_Transport');
        $ob = new $class($params);

        if (isset($this->_debug)) {
            $ob->getSMTPObject()->setDebug(true, array($this, 'smtpDebug'));
        }

        return $ob;
    }

    /**
     * SMTP debug handler.
     */
    public function smtpDebug($smtp, $message)
    {
        fwrite($this->_debug, $message);
        if (substr($message, -1) !== "\n") {
            fwrite($this->_debug, "\n");
        }
    }

}
