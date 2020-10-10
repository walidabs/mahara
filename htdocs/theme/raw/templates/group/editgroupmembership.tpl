<div class="modal fade js-editgroup-{$userid}" id="groupboxwrap-{$userid}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{str tag=Close}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h1 class="modal-title">{str tag=editmembershipforuser section=group arg1=display_name($userid)}</h1>
            </div>
            <div class="modal-body">
            {if !$data}
                <p class="no-results">{str tag=nogroups section=group}</p>
            {else}
                {foreach from=$data key=addtype item=groups}
                <div class="editgroup-container">
                {if $groups}
                    <h2 class="heading">{if $addtype == 'add'}{str tag=addmembers section=group}{else}{str tag=invite section=group}{/if}</h2>
                    <div class="checkboxes form-group last {if count($groups) > 6}column-list{/if}">
                        {foreach from=$groups item=group}
                            <div class="form-check group-invite">
                                <input id="{$addtype}{$group->id}" type="checkbox" class="form-check" name="{$addtype}group_{$userid}" value="{$group->id}" {if $group->checked}checked{/if} {if $group->disabled} disabled{/if}>
                                <label for="{$addtype}{$group->id}">{$group->name}</label>
                            </div>
                        {/foreach}
                    </div>
                    <a href="" class="btn btn-primary js-editgroup-submit" data-userid="{$userid}" data-addtype="{$addtype}">
                        {str tag=applychanges}
                    </a>
                {/if}
                </div>
                {/foreach}
            {/if}
            </div>
        </div>
    </div>
</div>
