<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Promotion;

final class PromotionApiTest extends Api
{
    public function testCreatePromotionAsAdmin(): void
    {
        $this->postAdmin('/api/promotions', [
            'code' => 'SAVE10',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'active' => true,
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreatePromotionAsNonAdminForbidden(): void
    {
        $this->post('/api/promotions', [
            'code' => 'SAVE10',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'active' => true,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreatePromotionRejectsInvalidType(): void
    {
        $this->postAdmin('/api/promotions', [
            'code' => 'SAVE10',
            'type' => 'custom',
            'value' => 10,
            'active' => true,
        ]);

        $this->assertResponseStatusCodeSame(422);
    }
}
