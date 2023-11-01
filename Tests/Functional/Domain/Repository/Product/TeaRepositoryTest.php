<?php

declare(strict_types=1);

namespace TTN\Tea\Tests\Functional\Domain\Repository\Product;

use TTN\Tea\Domain\Model\Product\Tea;
use TTN\Tea\Domain\Repository\Product\TeaRepository;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @covers \TTN\Tea\Domain\Repository\Product\TeaRepository
 * @covers \TTN\Tea\Domain\Model\Product\Tea
 */
final class TeaRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/tea'];

    private TeaRepository $subject;

    private PersistenceManagerInterface $persistenceManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persistenceManager = $this->get(PersistenceManagerInterface::class);

        $this->subject = $this->get(TeaRepository::class);
    }

    /**
     * @test
     */
    public function findAllForNoRecordsReturnsEmptyContainer(): void
    {
        $result = $this->subject->findAll();

        self::assertCount(0, $result);
    }

    /**
     * @test
     */
    public function findAllWithRecordsFindsRecordsFromAllPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/Tea.csv');

        $result = $this->subject->findAll();

        self::assertGreaterThanOrEqual(1, \count($result));
    }

    /**
     * @test
     */
    public function findAllSortsByTitleInAscendingOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/Tea.csv');

        $result = $this->subject->findAll();

        $result->rewind();
        self::assertSame(2, $result->current()->getUid());
    }

    /**
     * @test
     */
    public function findByUidForExistingRecordReturnsModel(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/Tea.csv');

        $uid = 1;
        $model = $this->subject->findByUid($uid);

        self::assertInstanceOf(Tea::class, $model);
    }

    /**
     * @test
     */
    public function findByUidForExistingRecordMapsAllScalarData(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/Tea.csv');

        $uid = 1;
        $model = $this->subject->findByUid($uid);
        self::assertInstanceOf(Tea::class, $model);

        self::assertSame('Earl Grey', $model->getTitle());
        self::assertSame('Fresh and hot.', $model->getDescription());
        self::assertSame(2, $model->getOwnerUid());
    }

    /**
     * @test
     */
    public function fillsImageRelation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/Tea.csv');

        $uid = 3;
        $model = $this->subject->findByUid($uid);

        $image = $model->getImage();
        self::assertInstanceOf(FileReference::class, $image);
        self::assertSame(1, $image->getUid());
    }

    /**
     * @test
     */
    public function addAndPersistAllCreatesNewRecord(): void
    {
        $title = 'Godesberger Burgtee';
        $model = new Tea();
        $model->setTitle($title);

        $this->subject->add($model);
        $this->persistenceManager->persistAll();

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_tea_domain_model_product_tea');
        $databaseRow = $connection
            ->executeQuery(
                'SELECT * FROM tx_tea_domain_model_product_tea WHERE uid = :uid',
                ['uid' => $model->getUid()]
            )
            ->fetchAssociative();

        self::assertIsArray($databaseRow);
        self::assertSame($title, $databaseRow['title']);
    }

    /**
     * @test
     */
    public function findByOwnerUidFindsTeaWithTheGivenOwnerUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/TeaWithOwner.csv');

        $result = $this->subject->findByOwnerUid(1);

        self::assertCount(1, $result);
    }

    /**
     * @test
     */
    public function findByOwnerUidFindsIgnoresTeaWithNonMatchingOwnerUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/TeaWithOwner.csv');

        $result = $this->subject->findByOwnerUid(2);

        self::assertCount(0, $result);
    }

    /**
     * @test
     */
    public function findByOwnerUidFindsIgnoresTeaWithZeroOwnerUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/TeaWithoutOwner.csv');

        $result = $this->subject->findByOwnerUid(1);

        self::assertCount(0, $result);
    }

    /**
     * @test
     */
    public function findByOwnerUidSortsByTitleInAscendingOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Product/TwoTeasWithOwner.csv');

        $result = $this->subject->findByOwnerUid(1);

        $result->rewind();
        self::assertSame(2, $result->current()->getUid());
    }
}
