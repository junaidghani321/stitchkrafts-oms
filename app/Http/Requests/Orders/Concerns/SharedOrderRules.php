<?php

namespace App\Http\Requests\Orders\Concerns;

/**
 * The three fields every order form has, plus the upload rules.
 */
trait SharedOrderRules
{
    protected function sharedRules(): array
    {
        return [
            'design_name' => ['required', 'string', 'max:191'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Artwork rules.
     *
     * Uses `extensions:` rather than `mimes:`. The mimes rule resolves the
     * extension through a MIME guesser, and embroidery/CAD formats (dst, emb,
     * pes, cdr, tap, cnd) have no MIME mapping — they are detected as
     * application/octet-stream, so `mimes:` rejects perfectly valid customer
     * files with a message nobody can act on.
     *
     * Checking the client extension is the right trade-off here: the files
     * land on a private, non-web-served disk and are renamed to a ULID on
     * write, so they are never executed or served directly.
     *
     * @param  bool  $required  false on edit, where existing artwork stands
     */
    protected function artworkRules(bool $required = true): array
    {
        $config = config('orders.uploads');

        // `extensions:` is a string rule — there is no Rule::extensions() helper.
        return [
            'artwork' => [$required ? 'required' : 'nullable', 'array', 'max:'.$config['max_files']],
            'artwork.*' => ['file', 'max:'.$config['max_size'], 'extensions:'.implode(',', $config['extensions'])],
        ];
    }

    /** The four yes/no selects on the digitizing form arrive as strings. */
    protected function toBoolean(?string $value): ?bool
    {
        return match ($value) {
            'yes' => true,
            'no' => false,
            default => null,
        };
    }

    protected function sharedMessages(): array
    {
        return [
            'artwork.required' => 'Please attach at least one artwork file.',
            'artwork.*.extensions' => 'That file type is not supported. Accepted: :values',
            'artwork.*.max' => 'Each file must be :max KB or smaller.',
            'customer_id.required' => 'Please choose the customer this order is for.',
        ];
    }
}
