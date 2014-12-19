<?php
/**
 * Horde_ActiveSync_Request_Settings::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle Settings requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
 */
class Horde_ActiveSync_Request_Settings extends Horde_ActiveSync_Request_Base
{
    /** Wbxml constants **/
    const SETTINGS_SETTINGS                 = 'Settings:Settings';
    const SETTINGS_STATUS                   = 'Settings:Status';
    const SETTINGS_GET                      = 'Settings:Get';
    const SETTINGS_SET                      = 'Settings:Set';
    const SETTINGS_OOF                      = 'Settings:Oof';
    const SETTINGS_OOFSTATE                 = 'Settings:OofState';
    const SETTINGS_STARTTIME                = 'Settings:StartTime';
    const SETTINGS_ENDTIME                  = 'Settings:EndTime';
    const SETTINGS_OOFMESSAGE               = 'Settings:OofMessage';
    const SETTINGS_APPLIESTOINTERNAL        = 'Settings:AppliesToInternal';
    const SETTINGS_APPLIESTOEXTERNALKNOWN   = 'Settings:AppliesToExternalKnown';
    const SETTINGS_APPLIESTOEXTERNALUNKNOWN = 'Settings:AppliesToExternalUnknown';
    const SETTINGS_ENABLED                  = 'Settings:Enabled';
    const SETTINGS_REPLYMESSAGE             = 'Settings:ReplyMessage';
    const SETTINGS_BODYTYPE                 = 'Settings:BodyType';
    const SETTINGS_DEVICEPASSWORD           = 'Settings:DevicePassword';
    const SETTINGS_PASSWORD                 = 'Settings:Password';
    const SETTINGS_DEVICEINFORMATION        = 'Settings:DeviceInformation';
    const SETTINGS_MODEL                    = 'Settings:Model';
    const SETTINGS_IMEI                     = 'Settings:IMEI';
    const SETTINGS_FRIENDLYNAME             = 'Settings:FriendlyName';
    const SETTINGS_OS                       = 'Settings:OS';
    const SETTINGS_OSLANGUAGE               = 'Settings:OSLanguage';
    const SETTINGS_PHONENUMBER              = 'Settings:PhoneNumber';
    const SETTINGS_USERINFORMATION          = 'Settings:UserInformation';
    const SETTINGS_EMAILADDRESSES           = 'Settings:EmailAddresses';
    const SETTINGS_SMTPADDRESS              = 'Settings:SmtpAddress';
    const SETTINGS_USERAGENT                = 'Settings:UserAgent';

    /** EAS 14.0 **/
    const SETTINGS_ENABLEOUTBOUNDSMS        = 'Settings:EnableOutboundSMS';
    const SETTINGS_MOBILEOPERATOR           = 'Settings:MobileOperator';

    /** EAS 14.1 **/
    const SETTINGS_PRIMARYSMTPADDRESS       = 'Settings:PrimarySmtpAddress';
    const SETTINGS_ACCOUNTS                 = 'Settings:Accounts';
    const SETTINGS_ACCOUNT                  = 'Settings:Account';
    const SETTINGS_ACCOUNTID                = 'Settings:AccountId';
    const SETTINGS_USERDISPLAYNAME          = 'Settings:UserDisplayName';


    /** Status codes **/
    const STATUS_SUCCESS                    = 1;
    const STATUS_ERROR                      = 2;
    const STATUS_UNAVAILABLE                = 4;

    /** Out of office constants **/
    const OOF_STATE_ENABLED                 = 1;
    const OOF_STATE_DISABLED                = 0;


    /**
     * Handle the request.
     *
     * @see Horde_ActiveSync_Request_Base::_handle()
     */
    protected function _handle()
    {
        if (!$this->_decoder->getElementStartTag(self::SETTINGS_SETTINGS)) {
            throw new Horde_ActiveSync_Exception('Protocol Errror');
        }

        $request = array();
        while (($reqtype = ($this->_decoder->getElementStartTag(self::SETTINGS_OOF) ? self::SETTINGS_OOF :
               ($this->_decoder->getElementStartTag(self::SETTINGS_DEVICEINFORMATION) ? self::SETTINGS_DEVICEINFORMATION :
               ($this->_decoder->getElementStartTag(self::SETTINGS_USERINFORMATION) ? self::SETTINGS_USERINFORMATION :
               ($this->_decoder->getElementStartTag(self::SETTINGS_DEVICEPASSWORD) ? self::SETTINGS_DEVICEPASSWORD :
               -1))))) != -1) {

            while (($querytype = ($this->_decoder->getElementStartTag(self::SETTINGS_GET) ? self::SETTINGS_GET :
                   ($this->_decoder->getElementStartTag(self::SETTINGS_SET) ? self::SETTINGS_SET :
                   -1))) != -1) {

                switch ($querytype) {
                case self::SETTINGS_GET:
                    switch ($reqtype) {
                    case self::SETTINGS_OOF:
                        $oof = Horde_ActiveSync::messageFactory('Oof');
                        $oof->decodeStream($this->_decoder);
                        $request['get']['oof']['bodytype'] = $oof->bodytype;
                        break;
                    case self::SETTINGS_USERINFORMATION:
                        // @TODO Do we need to fetch the <GET /> element?
                        $request['get']['userinformation'] = array();
                        break;
                    }
                    break;
                    $this->_decoder->getElementEndTag(); // SETTINGS_GET
                case self::SETTINGS_SET:
                    switch ($reqtype) {
                    case self::SETTINGS_OOF:
                        $oof = Horde_ActiveSync::messageFactory('Oof');
                        $oof->decodeStream($this->_decoder);

                        $request['set']['oof']['oofstate'] = $oof->state;
                        $request['set']['oof']['starttime'] = $oof->starttime;
                        $request['set']['oof']['endtime'] = $oof->endtime;
                        $message = array();
                        //$message['appliesto'] = $
                        $message['enabled'] = $oof->message->enabled;
                        $message['replymessage'] = $oof->message->reply;
                        $message['bodytype'] = $oof->message->bodytype;

                        if (!isset($request['set']['oof']['oofmsgs'])) {
                            $request['set']['oof']['oofmsgs'] = array();
                        }
                        $request['set']['oof']['oofmsgs'][] = $message;
                        break;
                    case self::SETTINGS_DEVICEINFORMATION :
                        // @TODO Clean the return values up when we can break bc.
                        $device_properties = $this->_device->properties;
                        $settings = Horde_ActiveSync::messageFactory('DeviceInformation');
                        $settings->decodeStream($this->_decoder);
                        $device_properties[self::SETTINGS_MODEL] = $settings->model;
                        $device_properties[self::SETTINGS_IMEI] = $settings->imei;
                        $device_properties[self::SETTINGS_FRIENDLYNAME] = $settings->friendlyname;
                        $device_properties[self::SETTINGS_OS] = $settings->os;
                        $device_properties[self::SETTINGS_OSLANGUAGE] = $settings->oslanguage;
                        $device_properties[self::SETTINGS_PHONENUMBER] = $settings->phonenumber;
                        $device_properties[self::SETTINGS_USERAGENT] = $settings->useragent;
                        $device_properties[self::SETTINGS_MOBILEOPERATOR] = $settings->mobileoperator;
                        $device_properties[self::SETTINGS_ENABLEOUTBOUNDSMS] = $settings->enableoutboundsms;

                        try {
                            $device_properties['version'] = $this->_device->version;
                            $this->_device->setDeviceProperties($device_properties);
                            $this->_device->save();
                        } catch (Horde_ActiveSync_Exception $e) {
                            $this->_logger->err($e->getMessage());
                            unset($device_properties);
                        }
                        break;
                    case self::SETTINGS_DEVICEPASSWORD :
                        $this->_decoder->getElementStartTag(self::SETTINGS_PASSWORD);
                        if (($password = $this->_decoder->getElementContent()) !== false) {
                            $this->_decoder->getElementEndTag(); // end $field
                        }
                        $request['set']['devicepassword'] = $password;
                        break;
                    }

                    $this->_decoder->getElementEndTag(); // SETTINGS_SET
                    break;
                }
            }
        }

        // SETTINGS_OOF || SETTINGS_DEVICEPW || SETTINGS_DEVICEINFORMATION || SETTINGS_USERINFORMATION
        $this->_decoder->getElementEndTag();
        $this->_decoder->getElementEndTag(); // SETTINGS


        // Tell the backend
        if (isset($request['set'])) {
            $result['set'] = $this->_driver->setSettings($request['set'], $this->_device->id);
        }
        if (isset($request['get'])) {
            $result['get'] = $this->_driver->getSettings($request['get'], $this->_device->id);
        }

        // Output response
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(self::SETTINGS_SETTINGS);
        $this->_encoder->startTag(self::SETTINGS_STATUS);
        $this->_encoder->content(self::STATUS_SUCCESS);
        $this->_encoder->endTag(); // end self::SETTINGS_STATUS
        if (isset($request['set']['oof'])) {
            $this->_encoder->startTag(self::SETTINGS_OOF);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            if (!isset($result['set']['oof'])) {
                $this->_encoder->content(self::OOF_STATE_DISABLED);
            } else {
                $this->_encoder->content($result['set']['oof']);
            }
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS
            $this->_encoder->endTag(); // end self::SETTINGS_OOF
        }
        if (isset($device_properties)) {
            $this->_encoder->startTag(self::SETTINGS_DEVICEINFORMATION);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            $this->_encoder->content(Horde_ActiveSync_Request_Settings::STATUS_SUCCESS);
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS
            $this->_encoder->endTag(); // end self::SETTINGS_DEVICEINFORMATION
        }
        if (isset($request['set']['devicepassword'])) {
            $this->_encoder->startTag(self::SETTINGS_DEVICEPASSWORD);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            if (!isset($result['set']['devicepassword'])) {
                $this->_encoder->content(0);
            } else {
                $this->_encoder->content($result['set']['devicepassword']);
            }
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS
            $this->_encoder->endTag(); // end self::SETTINGS_DEVICEPASSWORD
        }
        if (isset($request['get']['userinformation']) && isset($result['get']['userinformation'])) {
            $this->_encoder->startTag(self::SETTINGS_USERINFORMATION);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            $this->_encoder->content($result['get']['userinformation']['status']);
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS
            $this->_encoder->startTag(self::SETTINGS_GET);

            // @todo remove accounts existence check for H6.
            if ($this->_device->version >= Horde_ActiveSync::VERSION_FOURTEENONE &&
                !empty($result['get']['userinformation']['accounts'])) {
                $this->_encoder->startTag(self::SETTINGS_ACCOUNTS);
                foreach ($result['get']['userinformation']['accounts'] as $account) {
                    $this->_encoder->startTag(self::SETTINGS_ACCOUNT);

                    if (!empty($account['fullname'])) {
                        $this->_encoder->startTag(self::SETTINGS_USERDISPLAYNAME);
                        $this->_encoder->content($account['fullname']);
                        $this->_encoder->endTag();
                    }

                    if (!empty($account['emailaddresses'])) {
                        $this->_encoder->startTag(self::SETTINGS_EMAILADDRESSES);

                        $this->_encoder->startTag(self::SETTINGS_PRIMARYSMTPADDRESS);
                        $this->_encoder->content($account['emailaddresses'][0]);
                        $this->_encoder->endTag();

                        if (!empty($account['id'])) {
                            $this->_encoder->startTag(self::SETTINGS_ACCOUNTID);
                            $this->_encoder->content(array_pop($account['id']));
                            $this->_encoder->endTag();
                        }

                        foreach($account['emailaddresses'] as $value) {
                            $this->_encoder->startTag(self::SETTINGS_SMTPADDRESS);
                            $this->_encoder->content($value);
                            $this->_encoder->endTag(); // end self::SETTINGS_SMTPADDRESS
                        }

                        $this->_encoder->endTag(); // SETTINGS_EMAILADDRESSES
                    }

                    $this->_encoder->endTag(); // SETTINGS_ACCOUNT
                }
                $this->_encoder->endTag(); // SETTINGS_ACCOUNTS
            } else {
                $this->_encoder->startTag(self::SETTINGS_EMAILADDRESSES);
                if (!empty($result['get']['userinformation']['emailaddresses'])) {
                    foreach($result['get']['userinformation']['emailaddresses'] as $value) {
                        $this->_encoder->startTag(self::SETTINGS_SMTPADDRESS);
                        $this->_encoder->content($value);
                        $this->_encoder->endTag(); // end self::SETTINGS_SMTPADDRESS
                    }
                }
                $this->_encoder->endTag(); // end self::SETTINGS_EMAILADDRESSES
            }
            $this->_encoder->endTag(); // end self::SETTINGS_GET
            $this->_encoder->endTag(); // end self::SETTINGS_USERINFORMATION
        }
        if (isset($request['get']['oof'])) {
            $this->_encoder->startTag(self::SETTINGS_OOF);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            $this->_encoder->content($result['get']['oof']['status']);
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS

            if ($result['get']['oof']['status'] == self::STATUS_SUCCESS) {
                $this->_encoder->startTag(self::SETTINGS_GET);
                $this->_encoder->startTag(self::SETTINGS_OOFSTATE);
                $this->_encoder->content($result['get']['oof']['oofstate']);
                $this->_encoder->endTag(); // end self::SETTINGS_OOFSTATE
                // This we maybe need later on (OOFSTATE=2). It shows that OOF
                // Messages could be send depending on Time being set in here.
                // Unfortunately cannot proof it working on my device.
                if ($result['get']['oof']['oofstate'] == 2) {
                    $this->_encoder->startTag(self::SETTINGS_STARTTIME);
                    $this->_encoder->content(gmdate('Y-m-d\TH:i:s.000', $result['get']['oof']['starttime']));
                    $this->_encoder->endTag(); // end self::SETTINGS_STARTTIME
                    $this->_encoder->startTag(self::SETTINGS_ENDTIME);
                    $this->_encoder->content(gmdate('Y-m-d\TH:i:s.000', $result['get']['oof']['endtime']));
                    $this->_encoder->endTag(); // end self::SETTINGS_ENDTIME
                }
                foreach($result['get']['oof']['oofmsgs'] as $oofentry) {
                    $this->_encoder->startTag(self::SETTINGS_OOFMESSAGE);
                    $this->_encoder->startTag($oofentry['appliesto'],false,true);
                    $this->_encoder->startTag(self::SETTINGS_ENABLED);
                    $this->_encoder->content($oofentry['enabled']);
                    $this->_encoder->endTag(); // end self::SETTINGS_ENABLED
                    $this->_encoder->startTag(self::SETTINGS_REPLYMESSAGE);
                    $this->_encoder->content($oofentry['replymessage']);
                    $this->_encoder->endTag(); // end self::SETTINGS_REPLYMESSAGE
                    $this->_encoder->startTag(self::SETTINGS_BODYTYPE);
                    switch (strtolower($oofentry['bodytype'])) {
                    case 'text':
                        $this->_encoder->content('Text');
                        break;
                    case 'HTML':
                        $this->_encoder->content('HTML');
                    }
                    $this->_encoder->endTag(); // end self::SETTINGS_BODYTYPE
                    $this->_encoder->endTag(); // end self::SETTINGS_OOFMESSAGE
                }
                $this->_encoder->endTag(); // end self::SETTINGS_GET
                $this->_encoder->endTag(); // end self::SETTINGS_OOF
            }

        }

        $this->_encoder->endTag(); // end self::SETTINGS_SETTINGS

        return true;
    }

}