<?php
/**
 * jQuery Mobile page.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'mobile'));

$view = new Horde_View(array('templatePath' => IMP_TEMPLATES . '/mobile'));
new Horde_View_Helper_Text($view);

/* Initialize the IMP_Imap_Tree object. */
$imptree = $injector->getInstance('IMP_Imap_Tree');
$imptree->setIteratorFilter();
$view->tree = $imptree->createTree('mobile_folders', array(
    'poll_info' => true,
    'render_type' => 'IMP_Tree_Jquerymobile'
))->getTree(true);

$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
if ($view->allowFolders = $imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
    $view->options = IMP::flistSelect(array(
        'heading' => _("This message to"),
        'optgroup' => true,
        'inc_tasklists' => true,
        'inc_notepads' => true,
        'new_mbox' => true
    ));
}

$view->portal = Horde::getServiceLink('portal', 'horde')->setRaw(false);
$view->logout = Horde::getServiceLink('logout')->setRaw(false);
$view->canSearch = $imp_imap->access(IMP_Imap::ACCESS_SEARCH);
$view->canSpam = !empty($conf['spam']['reporting']);
$view->canHam = !empty($conf['notspam']['reporting']);

if ($view->canCompose = IMP::canCompose()) {
    /* Setting up identities. */
    $identity = $injector->getInstance('IMP_Identity');
    $view->defaultIdentity = $identity->getDefault();
    $view->identities = array();
    foreach ($identity->getSelectList() as $id => $from) {
        $view->identities[] = array(
            'label' => $from,
            'sel' => ($id == $identity->getDefault()),
            'val' => $id
        );
    }

    $view->composeCache = $injector->getInstance('IMP_Factory_Compose')->create()->getCacheId();
    $view->composeLink = Horde::getServiceLink('ajax', 'imp');
    $view->composeLink->pathInfo = 'addAttachment';
}

$page_output->addScriptFile('mobile.js');
require IMP_TEMPLATES . '/mobile/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));

/* Inline script. */
$page_output->addInlineScript(
    '$(window.document).bind("mobileinit", function() {
       $.mobile.page.prototype.options.addBackBtn = true;
       $.mobile.page.prototype.options.backBtnText = "' . _("Back") .'";
       $.mobile.dialog.prototype.options.closeBtnText = "' . _("Close") .'";
       $.mobile.loadingMessage = "' . _("loading") . '";
     });'
);

$page_output->deferScripts = true;
$page_output->header(array(
    'title' => _("Mobile Mail"),
    'view' => $registry::VIEW_SMARTMOBILE
));
echo $view->render('head.html.php');
echo $view->render('folders.html.php');
echo $view->render('mailbox.html.php');
echo $view->render('message.html.php');
if (IMP::canCompose()) {
    echo $view->render('compose.html.php');
}
if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
    echo $view->render('search.html.php');
}
echo $view->render('notice.html.php');
echo $view->render('confirm.html.php');
if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
    echo $view->render('target.html.php');
}
$page_output->footer();
