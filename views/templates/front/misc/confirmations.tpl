{*
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017-2024 thirty bees
 *  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{if isset($confirmations) && $confirmations}
  <div class="alert alert-info">
    <p>
      {if $confirmations|@count > 1}
        {l s='There are %d messages' sprintf=$confirmations|@count mod='tbgpdr'}
      {else}
        {l s='There is %d message' sprintf=$confirmations|@count mod='tbgpdr'}
      {/if}
    </p>
    <ol>
      {foreach from=$confirmations key=k item=confirmation}
        <li>{$confirmation}</li>
      {/foreach}
    </ol>
  </div>
{/if}
