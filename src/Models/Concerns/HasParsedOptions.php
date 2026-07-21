<?php

namespace Chanthoeun\FilamentCustomForms\Models\Concerns;

trait HasParsedOptions
{
    /**
     * Get the dynamically parsed options for the field.
     * Evaluates whether options should be sourced manually, from a model, or from an enum.
     */
    public function getParsedOptions(?string $locale = null): array
    {
        $options = [];
        $source = data_get($this->options, 'source', 'manual');

        $locale = $locale ?: app()->getLocale();
        $originalLocale = app()->getLocale();

        if ($originalLocale !== $locale) {
            app()->setLocale($locale);
        }

        if ($source === 'model' && $modelClass = data_get($this->options, 'model')) {
            $labelAttr = data_get($this->options, 'model_label_attribute', 'name');
            $valueAttr = data_get($this->options, 'model_value_attribute', 'id');
            if (class_exists($modelClass)) {
                $options = $modelClass::pluck($labelAttr, $valueAttr)->toArray();
            }
        } elseif ($source === 'enum' && $enumClass = data_get($this->options, 'enum')) {
            if (class_exists($enumClass) && enum_exists($enumClass)) {
                foreach ($enumClass::cases() as $case) {
                    $label = method_exists($case, 'getLabel') ? $case->getLabel() : $case->name;
                    $options[$case->value ?? $case->name] = $label;
                }
            }
        } else {
            $choices = data_get($this->options, 'choices', []);
            $locale = app()->getLocale();
            $fallback = config('app.fallback_locale', 'en');

            // Check if it's the new localized format by inspecting the first element
            if (! empty($choices)) {
                $firstElement = reset($choices);
                if (is_array($firstElement)) {
                    $choices = $choices[$locale] ?? $choices[$fallback] ?? [];
                }
            }
            $options = [];
            if (is_array($choices)) {
                foreach ($choices as $key => $val) {
                    // Try to translate the value if it's a translation key, otherwise it falls back to the original string.
                    $options[$key] = __($val);
                }
            }
        }

        if ($originalLocale !== $locale) {
            app()->setLocale($originalLocale);
        }

        return $options;
    }
}
