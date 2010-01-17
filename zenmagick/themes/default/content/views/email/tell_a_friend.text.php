<?php
/*
 * ZenMagick - Extensions for zen-cart
 * Copyright (C) 2006-2009 zenmagick.org
 *
 * Portions Copyright (c) 2003 The zen-cart developers
 * Portions Copyright (c) 2003 osCommerce
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * $Id$
 */
?>
<?php zm_l10n("Hi %s,", $emailMessage->getToName()) ?>


<?php zm_l10n("Your friend, %s, thought that you would be interested in %s from %s.", $emailMessage->getFromName(), $currentProduct->getName(), ZMSettings::get('storeName')) ?>

<?php if ($emailMessage->hasMessage()) { ?>

<?php zm_l10n("%s also sent a note saying:", $emailMessage->getFromName()) ?>

<?php echo $emailMessage->getMessage() ?>

<?php } ?>

<?php zm_l10n("To view the product, click on the following link or copy and paste the link into your web browser: %s", $net->product($currentProduct->getId(), null, false)) ?>


<?php zm_l10n("Regards, %s", ZMSettings::get('storeOwner')) ?>



<?php echo strip_tags($zm_theme->staticPageContent('email_advisory')) ?>
