<?php

/**
 * Copyright (c) 2022-present Tectalic (https://tectalic.com)
 *
 * For copyright and license information, please view the LICENSE file that was distributed with this source code.
 *
 * Please see the README.md file for usage instructions.
 */

declare(strict_types=1);

namespace Tectalic\OpenAi\Models\ImagesGenerations;

use Tectalic\OpenAi\Models\AbstractModel;

final class CreateResponseDataItem extends AbstractModel
{
    /** @var string */
    public $url;

    /** @var string */
    public $b64_json;
}
