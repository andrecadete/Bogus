<?php
namespace TijmenWierenga\Bogus\Tests\Container;

use DI\ContainerBuilder;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use TijmenWierenga\Bogus\Collection\Collection;
use TijmenWierenga\Bogus\Config\ConfigFile;
use TijmenWierenga\Bogus\Config\YamlConfig;
use TijmenWierenga\Bogus\Fixtures;
use TijmenWierenga\Bogus\Generator\MappingFile\Mappable;
use TijmenWierenga\Bogus\Generator\MappingFile\MappingFileFactory;
use TijmenWierenga\Bogus\Storage\Repository\RepositoryStorage;
use function DI\object;
use function DI\get;
use TijmenWierenga\Bogus\Tests\User;

class ContainerTest extends TestCase
{
    /**
    * @test
    */
    public function it_resolves_a_repository_through_dependency_injection()
    {
        $container = $this->createTestContainer();
        $config = new YamlConfig(new ConfigFile(__DIR__ . '/ContainerConfig.yml'));
        $storage = new RepositoryStorage($container, $config);
        $generator = new MappingFileFactory($config);
        $fixtures = new Fixtures($storage, $generator);

        /** @var User[]|Collection $users */
        $users = $fixtures->create(User::class);

        $this->assertContainsOnlyInstancesOf(User::class, $users);
        $this->assertEquals('TijmenWierenga', $users->first()->getName());
    }

    /**
     * @return ContainerInterface
     */
    private function createTestContainer(): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
             'test.repository' => object(UserRepository::class)->constructor(get(StorageMechanism::class))
        ]);

        return $builder->build();
    }
}

class StorageMechanism
{
    public function persist(User $user) {}
    public function flush() {}
}

class UserRepository
{

    /**
     * @var StorageMechanism
     */
    private $storage;

    /**
     * UserRepository constructor.
     * @param StorageMechanism $storage
     */
    public function __construct(StorageMechanism $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param iterable $users
     */
    public function save(iterable $users)
    {
        foreach ($users as $user) {
            $this->storage->persist($user);
        }

        $this->storage->flush();
    }
}

class UserMapper implements Mappable
{
    public static function build(iterable $attributes)
    {
        return new User("TijmenWierenga");
    }
}