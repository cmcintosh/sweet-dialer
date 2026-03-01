<!--
  Phone Numbers List Template
  Sweet-Dialer Twilio Phone Numbers Display
-->

{$MODULE_TITLE}

<div class="moduleTitle">
    <h2>{$MOD.LBL_TWILIO_PHONE_NUMBERS|default:'Twilio Phone Numbers'}</h2>
</div>

<div class="clear"></div>

<div class="listViewBody">
    {if $ERROR_MESSAGE}
        <div class="alert alert-warning" role="alert">
            <strong>⚠ {$APP.LBL_NOTICE}</strong>: {$ERROR_MESSAGE}
        </div>
    {/if}

    {if !$ERROR_MESSAGE || $PHONE_NUMBERS}
        <div class="action_buttons" style="margin-bottom: 15px;">
            <a href="index.php?module=outr_CtiSettings&action=EditView" class="button primary">
                {$MOD.LBL_CONFIGURE_CTI|default:'Configure CTI Settings'}
            </a>
            <a href="index.php?module=outr_CtiSettings&action=phonenumbers" class="button">
                {$MOD.LBL_REFRESH|default:'Refresh'}
            </a>
        </div>

        {if $PHONE_NUMBERS}
            <table class="list view" id="phoneNumbersTable">
                <thead>
                    <tr height="20">
                        <th scope="col" nowrap="nowrap">
                            {$MOD.LBL_PHONE_NUMBER|default:'Phone Number'}
                        </th>
                        <th scope="col" nowrap="nowrap">
                            {$MOD.LBL_FRIENDLY_NAME|default:'Friendly Name'}
                        </th>
                        <th scope="col" nowrap="nowrap">
                            {$MOD.LBL_PHONE_SID|default:'Phone SID'}
                        </th>
                        <th scope="col" nowrap="nowrap" style="text-align: center;">
                            {$MOD.LBL_VOICE_CAPABLE|default:'Voice Capable'}
                        </th>
                        <th scope="col" nowrap="nowrap" style="text-align: center;">
                            {$MOD.LBL_SMS_CAPABLE|default:'SMS Capable'}
                        </th>
                        <th scope="col" nowrap="nowrap">
                            {$MOD.LBL_ASSIGNMENT_STATUS|default:'Assignment Status'}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$PHONE_NUMBERS item=number}
                        <tr height="20" class="{cycle values='oddListRowS1,evenListRowS1'}">
                            <td scope="row" nowrap="nowrap">
                                <strong>{$number.number}</strong>
                            </td>
                            <td nowrap="nowrap">
                                {$number.friendly_name|default:'N/A'}
                            </td>
                            <td nowrap="nowrap">
                                <span class="sugar_field" title="{$number.sid}">
                                    {$number.sid|truncate:20:"...":true}
                                </span>
                            </td>
                            <td align="center" nowrap="nowrap">
                                {if $number.capabilities.voice}
                                    <span class="label label-success">✓ {$APP.LBL_YES}</span>
                                {else}
                                    <span class="label label-default">✗ {$APP.LBL_NO}</span>
                                {/if}
                            </td>
                            <td align="center" nowrap="nowrap">
                                {if $number.capabilities.sms}
                                    <span class="label label-success">✓ {$APP.LBL_YES}</span>
                                {else}
                                    <span class="label label-default">✗ {$APP.LBL_NO}</span>
                                {/if}
                            </td>
                            <td nowrap="nowrap">
                                {assign var="phoneSid" value=$number.sid}
                                {if in_array($phoneSid, $ASSIGNED_SIDS)}
                                    <span class="label label-info">
                                        ⭐ {$MOD.LBL_ASSIGNED|default:'Assigned'}
                                    </span>
                                {else}
                                    <span class="label label-default">
                                        {$MOD.LBL_UNASSIGNED|default:'Unassigned'}
                                    </span>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>

            <div class="pagination" style="margin-top: 10px;">
                <p>{$MOD.LBL_TOTAL_NUMBERS|default:'Total Numbers'}: {$PHONE_NUMBERS|@count}</p>
            </div>
        {else}
            {if !$ERROR_MESSAGE}
                <div class="alert alert-info">
                    {$MOD.LBL_NO_PHONE_NUMBERS|default:'No phone numbers found in your Twilio account. Add numbers in your Twilio console to see them here.'}
                </div>
            {/if}
        {/if}
    {/if}
</div>
