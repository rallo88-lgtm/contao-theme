<?php

namespace Rallo\ContaoTheme\DCA;

use Contao\DataContainer;

class IconPickerWizard
{
    public function generate(DataContainer $dc): string
    {
        $fieldId = 'ctrl_' . $dc->field;
        $url     = '/contao/rct-icon-picker?field=' . rawurlencode($fieldId);

        return sprintf(
            '<span style="float:right;margin-top:-28px;margin-right:4px;position:relative;z-index:10;"><a href="%s" onclick="window.open(this.href,\'rct_icon_picker\',\'width=900,height=700,scrollbars=yes,resizable=yes\');return false;" title="Icon-Picker öffnen" style="display:inline-block;padding:4px 10px;background:#27c4f4;color:#0a0a0a;border-radius:3px;text-decoration:none;font-size:0.72rem;font-weight:600;line-height:1;">🎨 Picker</a></span>',
            $url
        );
    }
}
