<?php

namespace Chanthoeun\FilamentCustomForms\Observers;

use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Models\CustomFormEntry;

class CustomFormObserver
{
    public function created(CustomForm $customForm)
    {
        if (class_exists(\Chanthoeun\FilamentDocumentBuilder\Models\DocumentTemplate::class)) {
            \Chanthoeun\FilamentDocumentBuilder\Models\DocumentTemplate::create([
                'name' => $customForm->name . ' Template',
                'type' => 'custom_form_' . $customForm->id,
                'model_class' => CustomFormEntry::class,
                'content' => '',
            ]);
        }
    }
}
