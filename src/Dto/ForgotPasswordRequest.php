<?php
/**
 * User: Andreas Warnaar
 * Date: 9-3-18
 * Time: 21:48
 */

namespace App\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Jslfjsdlkj fslkfj sdlkfja f;lasjkf; lskjf;lasdkj fasd;ljkf sd;lfjs.
 *
 * @ApiResource(
 *      collectionOperations={
 *          "post"={
 *              "path"="/users/forgot-password-request",
 *          },
 *      },
 *      itemOperations={},
 * )
 *
 */
final class ForgotPasswordRequest
{
    /**
     *
     * @var string Kip sdaata
     * @Assert\NotBlank
     * @Assert\Email
     */
    public $email;
}
