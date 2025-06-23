<?php

declare(strict_types=1);

namespace Mehrwert\FalQuota\Evaluation;

/*
 * 2019 - EXT:fal_quota - Configuration fields for Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

/**
 * Class for field value validation/evaluation to be used in 'eval' of TCA
 */
class StorageQuotaEvaluation
{
    /**
     * Server-side validation/evaluation on saving the record
     *
     * @see \Mehrwert\FalQuota\Form\Element\MegaByteInputElement
     *
     * @param string $value The field value to be evaluated
     * @param string $is_in The "is_in" value of the field configuration from TCA
     * @param bool $set boolean defining if the value is written to the database or not
     */
    public function evaluateFieldValue(string $value, string $is_in, bool &$set): int
    {
        return (int)trim($value) * (1024 ** 2);
    }
}
