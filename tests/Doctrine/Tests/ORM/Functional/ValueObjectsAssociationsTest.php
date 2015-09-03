<?php

namespace Doctrine\Tests\ORM\Functional;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * @group DDC-93
 */
class ValueObjectsAssociationsTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDCEmbeddableManyToOne'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDCEmbeddableOneToMany'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDCEmbeddableManyToMany'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDCEmbeddableOneToOne'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\BidirectionalOne2ManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\UnidirectionalOne2ManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\ManyToOneEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\UnidirectionalManyToManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\BidirectionalManyToManyEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\UnidirectionalOneToOneEntity'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\BidirectionalOneToOneEntity'),
            ));
        } catch(\Exception $e) {
        }
    }

    public function testMetadataHasReflectionEmbeddablesAccessible()
    {
        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDCEmbeddableManyToOne');
        $this->assertInstanceOf('Doctrine\Common\Reflection\RuntimePublicReflectionProperty', $classMetadata->getReflectionProperty('embed'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.unidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectional'));

        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDCEmbeddableOneToMany');
        $this->assertInstanceOf('Doctrine\Common\Reflection\RuntimePublicReflectionProperty', $classMetadata->getReflectionProperty('embed'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.entities'));

        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDCEmbeddableManyToMany');
        $this->assertInstanceOf('Doctrine\Common\Reflection\RuntimePublicReflectionProperty', $classMetadata->getReflectionProperty('embed'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.unidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectionalInversed'));

        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDCEmbeddableOneToOne');
        $this->assertInstanceOf('Doctrine\Common\Reflection\RuntimePublicReflectionProperty', $classMetadata->getReflectionProperty('embed'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.unidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectional'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ReflectionEmbeddedProperty', $classMetadata->getReflectionProperty('embed.bidirectionalInversed'));
    }

    public function testCRUDManyToOne()
    {
        $relatedBidirectional = new BidirectionalOne2ManyEntity();
        $relatedUnidirectional = new UnidirectionalOne2ManyEntity();
        $this->_em->persist($relatedBidirectional);
        $this->_em->persist($relatedUnidirectional);

        $entity = new DDCEmbeddableManyToOne();
        $entity->embed->bidirectional = $relatedBidirectional;
        $entity->embed->unidirectional = $relatedUnidirectional;

        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $entity = $this->_em->find(DDCEmbeddableManyToOne::CLASSNAME, $entity->id);

        $this->assertInstanceOf(DDCEmbedManyToOne::CLASSNAME, $entity->embed);

        $this->assertInstanceOf(BidirectionalOne2ManyEntity::CLASSNAME, $entity->embed->bidirectional);
        $this->assertEquals($relatedBidirectional->id, $entity->embed->bidirectional->id);

        $this->assertInstanceOf(UnidirectionalOne2ManyEntity::CLASSNAME, $entity->embed->unidirectional);
        $this->assertEquals($relatedUnidirectional->id, $entity->embed->unidirectional->id);

        // 3. check changing value objects works
        $relatedBidirectional2 = new BidirectionalOne2ManyEntity();
        $relatedUnidirectional2 = new UnidirectionalOne2ManyEntity();
        $this->_em->persist($relatedBidirectional2);
        $this->_em->persist($relatedUnidirectional2);

        $entity->embed->bidirectional = $relatedBidirectional2;
        $entity->embed->unidirectional = $relatedUnidirectional2;

        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(DDCEmbeddableManyToOne::CLASSNAME, $entity->id);

        $this->assertInstanceOf(DDCEmbedManyToOne::CLASSNAME, $entity->embed);

        $this->assertInstanceOf(BidirectionalOne2ManyEntity::CLASSNAME, $entity->embed->bidirectional);
        $this->assertEquals($relatedBidirectional2->id, $entity->embed->bidirectional->id);

        $this->assertInstanceOf(UnidirectionalOne2ManyEntity::CLASSNAME, $entity->embed->unidirectional);
        $this->assertEquals($relatedUnidirectional2->id, $entity->embed->unidirectional->id);

        // 4. check deleting works
        $entityId = $entity->id;;
        $this->_em->remove($entity);
        $this->_em->flush();

        $this->assertNull($this->_em->find(DDCEmbeddableManyToOne::CLASSNAME, $entityId));
    }

    public function testCRUDOneToMany()
    {
        $related = array(
            new ManyToOneEntity(),
            new ManyToOneEntity(),
        );
        $this->_em->persist($related[0]);
        $this->_em->persist($related[1]);

        $entity = new DDCEmbeddableOneToMany();
        $entity->embed->entities = $related;
        $related[0]->property = $entity;
        $related[1]->property = $entity;

        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $entity = $this->_em->find(DDCEmbeddableOneToMany::CLASSNAME, $entity->id);

        $this->assertInstanceOf(DDCEmbedOneToMany::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(ManyToOneEntity::CLASSNAME, $entity->embed->entities[0]);
        $this->assertInstanceOf(ManyToOneEntity::CLASSNAME, $entity->embed->entities[1]);
        $this->assertEquals($related[0]->id, $entity->embed->entities[0]->id);
        $this->assertEquals($related[1]->id, $entity->embed->entities[1]->id);

        // 3. check changing value objects works
        $related2 = array(
            new ManyToOneEntity(),
            new ManyToOneEntity(),
        );
        $this->_em->persist($related2[0]);
        $this->_em->persist($related2[1]);

        $this->_em->remove($entity->embed->entities[0]);
        $this->_em->remove($entity->embed->entities[1]);

        $entity->embed->entities = $related2;
        $related2[0]->property = $entity;
        $related2[1]->property = $entity;

        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(DDCEmbeddableOneToMany::CLASSNAME, $entity->id);

        $this->assertInstanceOf(DDCEmbedOneToMany::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(ManyToOneEntity::CLASSNAME, $entity->embed->entities[0]);
        $this->assertInstanceOf(ManyToOneEntity::CLASSNAME, $entity->embed->entities[1]);
        $this->assertEquals($related2[0]->id, $entity->embed->entities[0]->id);
        $this->assertEquals($related2[1]->id, $entity->embed->entities[1]->id);

        // 4. check deleting works
        $entityId = $entity->id;;
        $this->_em->remove($entity);
        $this->_em->flush();

        $this->assertNull($this->_em->find(DDCEmbeddableOneToMany::CLASSNAME, $entityId));
    }

    public function testCRUDManyToMany()
    {
        $relatedUni = array(
            new UnidirectionalManyToManyEntity(),
            new UnidirectionalManyToManyEntity(),
        );

        $relatedBi = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $relatedBiInversed = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $this->_em->persist($relatedUni[0]);
        $this->_em->persist($relatedUni[1]);
        $this->_em->persist($relatedBi[0]);
        $this->_em->persist($relatedBi[1]);
        $this->_em->persist($relatedBiInversed[0]);
        $this->_em->persist($relatedBiInversed[1]);

        $entity = new DDCEmbeddableManyToMany();
        $entity->embed->unidirectional = $relatedUni;
        $entity->embed->bidirectional = $relatedBi;
        $entity->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed[0]->propertyInversed = array($entity);
        $relatedBiInversed[1]->propertyInversed = array($entity);

        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $entity = $this->_em->find(DDCEmbeddableManyToMany::CLASSNAME, $entity->id);

        $this->assertInstanceOf(DDCEmbedManyToMany::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(UnidirectionalManyToManyEntity::CLASSNAME, $entity->embed->unidirectional[0]);
        $this->assertInstanceOf(UnidirectionalManyToManyEntity::CLASSNAME, $entity->embed->unidirectional[1]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectional[0]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectional[1]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectionalInversed[0]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectionalInversed[1]);
        $this->assertEquals($relatedUni[0]->id, $entity->embed->unidirectional[0]->id);
        $this->assertEquals($relatedUni[1]->id, $entity->embed->unidirectional[1]->id);
        $this->assertEquals($relatedBi[0]->id, $entity->embed->bidirectional[0]->id);
        $this->assertEquals($relatedBi[1]->id, $entity->embed->bidirectional[1]->id);
        $this->assertEquals($relatedBiInversed[0]->id, $entity->embed->bidirectionalInversed[0]->id);
        $this->assertEquals($relatedBiInversed[1]->id, $entity->embed->bidirectionalInversed[1]->id);

        // 3. check changing value objects works
        $relatedUni2 = array(
            new UnidirectionalManyToManyEntity(),
            new UnidirectionalManyToManyEntity(),
        );

        $relatedBi2 = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $relatedBiInversed2 = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $this->_em->persist($relatedUni2[0]);
        $this->_em->persist($relatedUni2[1]);
        $this->_em->persist($relatedBi2[0]);
        $this->_em->persist($relatedBi2[1]);
        $this->_em->persist($relatedBiInversed2[0]);
        $this->_em->persist($relatedBiInversed2[1]);

        $this->_em->remove($entity->embed->bidirectionalInversed[0]);
        $this->_em->remove($entity->embed->bidirectionalInversed[1]);

        $entity->embed->unidirectional = $relatedUni2;
        $entity->embed->bidirectional = $relatedBi2;
        $entity->embed->bidirectionalInversed = $relatedBiInversed2;
        $relatedBiInversed2[0]->propertyInversed = array($entity);
        $relatedBiInversed2[1]->propertyInversed = array($entity);

        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(DDCEmbeddableManyToMany::CLASSNAME, $entity->id);

        $this->assertInstanceOf(DDCEmbedManyToMany::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(UnidirectionalManyToManyEntity::CLASSNAME, $entity->embed->unidirectional[0]);
        $this->assertInstanceOf(UnidirectionalManyToManyEntity::CLASSNAME, $entity->embed->unidirectional[1]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectional[0]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectional[1]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectionalInversed[0]);
        $this->assertInstanceOf(BidirectionalManyToManyEntity::CLASSNAME, $entity->embed->bidirectionalInversed[1]);
        $this->assertEquals($relatedUni2[0]->id, $entity->embed->unidirectional[0]->id);
        $this->assertEquals($relatedUni2[1]->id, $entity->embed->unidirectional[1]->id);
        $this->assertEquals($relatedBi2[0]->id, $entity->embed->bidirectional[0]->id);
        $this->assertEquals($relatedBi2[1]->id, $entity->embed->bidirectional[1]->id);
        $this->assertEquals($relatedBiInversed2[0]->id, $entity->embed->bidirectionalInversed[0]->id);
        $this->assertEquals($relatedBiInversed2[1]->id, $entity->embed->bidirectionalInversed[1]->id);

        // 4. check deleting works
        $entityId = $entity->id;;
        $this->_em->remove($entity);
        $this->_em->flush();

        $this->assertNull($this->_em->find(DDCEmbeddableManyToMany::CLASSNAME, $entityId));
    }

    public function testCRUDOneToOne()
    {
        $relatedUni = new UnidirectionalOneToOneEntity();
        $relatedBi = new BidirectionalOneToOneEntity();
        $relatedBiInversed = new BidirectionalOneToOneEntity();
        $this->_em->persist($relatedUni);
        $this->_em->persist($relatedBi);
        $this->_em->persist($relatedBiInversed);

        $entity = new DDCEmbeddableOneToOne();
        $entity->embed->unidirectional = $relatedUni;
        $entity->embed->bidirectional = $relatedBi;
        $entity->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed->propertyInversed = $entity;

        $this->_em->persist($entity);

        $this->_em->flush();

        $this->_em->clear();

        // 2. check loading value objects works
        $entity = $this->_em->find(DDCEmbeddableOneToOne::CLASSNAME, $entity->id);

        $this->assertInstanceOf(DDCEmbedOneToOne::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(UnidirectionalOneToOneEntity::CLASSNAME, $entity->embed->unidirectional);
        $this->assertInstanceOf(BidirectionalOneToOneEntity::CLASSNAME, $entity->embed->bidirectional);
        $this->assertInstanceOf(BidirectionalOneToOneEntity::CLASSNAME, $entity->embed->bidirectionalInversed);
        $this->assertEquals($relatedUni->id, $entity->embed->unidirectional->id);
        $this->assertEquals($relatedBi->id, $entity->embed->bidirectional->id);
        $this->assertEquals($relatedBiInversed->id, $entity->embed->bidirectionalInversed->id);

        // 3. check changing value objects works
        $relatedUni2 = new UnidirectionalOneToOneEntity();
        $relatedBi2 = new BidirectionalOneToOneEntity();
        $relatedBiInversed2 = new BidirectionalOneToOneEntity();

        $this->_em->persist($relatedUni2);
        $this->_em->persist($relatedBi2);
        $this->_em->persist($relatedBiInversed2);

        $this->_em->remove($entity->embed->bidirectionalInversed);

        //FuckFuckFuck. Flush remove entity to database to avoid unique constraint violation, because inserts runs before delete in flush
        $this->_em->flush();

        $entity->embed->unidirectional = $relatedUni2;
        $entity->embed->bidirectional = $relatedBi2;
        $entity->embed->bidirectionalInversed = $relatedBiInversed2;
        $relatedBiInversed2->propertyInversed = $entity;

        $this->_em->flush();

        $this->_em->clear();

        $entity = $this->_em->find(DDCEmbeddableOneToOne::CLASSNAME, $entity->id);

        $this->assertInstanceOf(DDCEmbedOneToOne::CLASSNAME, $entity->embed);
        $this->assertInstanceOf(UnidirectionalOneToOneEntity::CLASSNAME, $entity->embed->unidirectional);
        $this->assertInstanceOf(BidirectionalOneToOneEntity::CLASSNAME, $entity->embed->bidirectional);
        $this->assertInstanceOf(BidirectionalOneToOneEntity::CLASSNAME, $entity->embed->bidirectionalInversed);
        $this->assertEquals($relatedUni2->id, $entity->embed->unidirectional->id);
        $this->assertEquals($relatedBi2->id, $entity->embed->bidirectional->id);
        $this->assertEquals($relatedBiInversed2->id, $entity->embed->bidirectionalInversed->id);

        // 4. check deleting works
        $entityId = $entity->id;;
        $this->_em->remove($entity);
        $this->_em->flush();

        $this->assertNull($this->_em->find(DDCEmbeddableOneToOne::CLASSNAME, $entityId));
    }

    public function testLoadDqlManyToOne()
    {
        $relatedBidirectional = new BidirectionalOne2ManyEntity();
        $relatedUnidirectional = new UnidirectionalOne2ManyEntity();
        $this->_em->persist($relatedBidirectional);
        $this->_em->persist($relatedUnidirectional);

        $entities = [];

        $entities[0] = new DDCEmbeddableManyToOne();
        $entities[0]->embed->bidirectional = $relatedBidirectional;
        $entities[0]->embed->unidirectional = $relatedUnidirectional;

        $entities[1] = new DDCEmbeddableManyToOne();
        $entities[1]->embed->bidirectional = $relatedBidirectional;
        $entities[1]->embed->unidirectional = $relatedUnidirectional;

        $entities[2] = new DDCEmbeddableManyToOne();
        $entities[2]->embed->bidirectional = $relatedBidirectional;
        $entities[2]->embed->unidirectional = $relatedUnidirectional;

        $this->_em->persist($entities[0]);
        $this->_em->persist($entities[1]);
        $this->_em->persist($entities[2]);

        $this->_em->flush();
        $this->_em->clear();

        $dql = "
          SELECT p, unidirectional, bidirectional FROM " . __NAMESPACE__ . "\DDCEmbeddableManyToOne p
          INNER JOIN p.embed.unidirectional unidirectional
          INNER JOIN p.embed.bidirectional bidirectional
        ";

        $found = $this->_em->createQuery($dql)->getResult();

        $this->assertCount(3, $found);

        $this->assertEquals($entities[0]->embed->unidirectional->id, $found[0]->embed->unidirectional->id);
        $this->assertEquals($entities[0]->embed->bidirectional->id, $found[0]->embed->bidirectional->id);

        $this->assertEquals($entities[1]->embed->unidirectional->id, $found[1]->embed->unidirectional->id);
        $this->assertEquals($entities[1]->embed->bidirectional->id, $found[1]->embed->bidirectional->id);

        $this->assertEquals($entities[2]->embed->unidirectional->id, $found[2]->embed->unidirectional->id);
        $this->assertEquals($entities[2]->embed->bidirectional->id, $found[2]->embed->bidirectional->id);

        $found = $this->_em->createQuery($dql)->getArrayResult();

        $this->assertCount(3, $found);

        $this->assertEquals($entities[0]->embed->unidirectional->id, $found[0]['embed.unidirectional']['id']);
        $this->assertEquals($entities[0]->embed->bidirectional->id, $found[0]['embed.bidirectional']['id']);

        $this->assertEquals($entities[1]->embed->unidirectional->id, $found[1]['embed.unidirectional']['id']);
        $this->assertEquals($entities[1]->embed->bidirectional->id, $found[1]['embed.bidirectional']['id']);

        $this->assertEquals($entities[2]->embed->unidirectional->id, $found[2]['embed.unidirectional']['id']);
        $this->assertEquals($entities[2]->embed->bidirectional->id, $found[2]['embed.bidirectional']['id']);
    }

    public function testLoadDqlOneToMany()
    {
        //Fuck, we give not empty table on test start
        $this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . "\ManyToOneEntity")->execute();
        $entities = [];
        $related = array(
            new ManyToOneEntity(),
            new ManyToOneEntity(),
        );
        $this->_em->persist($related[0]);
        $this->_em->persist($related[1]);

        $entities[0] = new DDCEmbeddableOneToMany();
        $entities[0]->embed->entities = $related;
        $related[0]->property = $entities[0];
        $related[1]->property = $entities[0];

        $related = array(
            new ManyToOneEntity(),
            new ManyToOneEntity(),
        );
        $this->_em->persist($related[0]);
        $this->_em->persist($related[1]);

        $entities[1] = new DDCEmbeddableOneToMany();
        $entities[1]->embed->entities = $related;
        $related[0]->property = $entities[1];
        $related[1]->property = $entities[1];

        $this->_em->persist($entities[0]);
        $this->_em->persist($entities[1]);

        $this->_em->flush();
        $this->_em->clear();

        $dql = "
          SELECT p, entities FROM " . __NAMESPACE__ . "\DDCEmbeddableOneToMany p
          INNER JOIN p.embed.entities entities
        ";

        $found = $this->_em->createQuery($dql)->getResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->entities[0]->id, $found[0]->embed->entities[0]->id);
        $this->assertEquals($entities[0]->embed->entities[1]->id, $found[0]->embed->entities[1]->id);

        $this->assertEquals($entities[1]->embed->entities[0]->id, $found[1]->embed->entities[0]->id);
        $this->assertEquals($entities[1]->embed->entities[1]->id, $found[1]->embed->entities[1]->id);

        $found = $this->_em->createQuery($dql)->getArrayResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->entities[0]->id, $found[0]['embed.entities'][0]['id']);
        $this->assertEquals($entities[0]->embed->entities[1]->id, $found[0]['embed.entities'][1]['id']);

        $this->assertEquals($entities[1]->embed->entities[0]->id, $found[1]['embed.entities'][0]['id']);
        $this->assertEquals($entities[1]->embed->entities[1]->id, $found[1]['embed.entities'][1]['id']);
    }

    public function testLoadDqlManyToMany()
    {
        //Fuck, we give not empty table on test start
        $this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . "\UnidirectionalManyToManyEntity")->execute();
        $this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . "\BidirectionalManyToManyEntity")->execute();

        $relatedUni = array(
            new UnidirectionalManyToManyEntity(),
            new UnidirectionalManyToManyEntity(),
        );

        $relatedBi = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $relatedBiInversed = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $this->_em->persist($relatedUni[0]);
        $this->_em->persist($relatedUni[1]);
        $this->_em->persist($relatedBi[0]);
        $this->_em->persist($relatedBi[1]);
        $this->_em->persist($relatedBiInversed[0]);
        $this->_em->persist($relatedBiInversed[1]);

        $entities[0] = new DDCEmbeddableManyToMany();
        $entities[0]->embed->unidirectional = $relatedUni;
        $entities[0]->embed->bidirectional = $relatedBi;
        $entities[0]->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed[0]->propertyInversed = array($entities[0]);
        $relatedBiInversed[1]->propertyInversed = array($entities[0]);

        $this->_em->persist($entities[0]);

        $relatedUni = array(
            new UnidirectionalManyToManyEntity(),
            new UnidirectionalManyToManyEntity(),
        );

        $relatedBi = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $relatedBiInversed = array(
            new BidirectionalManyToManyEntity(),
            new BidirectionalManyToManyEntity(),
        );

        $this->_em->persist($relatedUni[0]);
        $this->_em->persist($relatedUni[1]);
        $this->_em->persist($relatedBi[0]);
        $this->_em->persist($relatedBi[1]);
        $this->_em->persist($relatedBiInversed[0]);
        $this->_em->persist($relatedBiInversed[1]);

        $entities[1] = new DDCEmbeddableManyToMany();
        $entities[1]->embed->unidirectional = $relatedUni;
        $entities[1]->embed->bidirectional = $relatedBi;
        $entities[1]->embed->bidirectionalInversed = $relatedBiInversed;
        $relatedBiInversed[0]->propertyInversed = array($entities[1]);
        $relatedBiInversed[1]->propertyInversed = array($entities[1]);

        $this->_em->persist($entities[1]);

        $this->_em->flush();
        $this->_em->clear();

        $dql = "
          SELECT p, unidirectional, bidirectional, bidirectionalInversed FROM " . __NAMESPACE__ . "\DDCEmbeddableManyToMany p
          INNER JOIN p.embed.unidirectional unidirectional
          INNER JOIN p.embed.bidirectional bidirectional
          INNER JOIN p.embed.bidirectionalInversed bidirectionalInversed
        ";

        $found = $this->_em->createQuery($dql)->getResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->unidirectional[0]->id, $found[0]->embed->unidirectional[0]->id);
        $this->assertEquals($entities[0]->embed->unidirectional[1]->id, $found[0]->embed->unidirectional[1]->id);
        $this->assertEquals($entities[0]->embed->bidirectional[0]->id, $found[0]->embed->bidirectional[0]->id);
        $this->assertEquals($entities[0]->embed->bidirectional[1]->id, $found[0]->embed->bidirectional[1]->id);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed[0]->id, $found[0]->embed->bidirectionalInversed[0]->id);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed[1]->id, $found[0]->embed->bidirectionalInversed[1]->id);

        $this->assertEquals($entities[1]->embed->unidirectional[0]->id, $found[1]->embed->unidirectional[0]->id);
        $this->assertEquals($entities[1]->embed->unidirectional[1]->id, $found[1]->embed->unidirectional[1]->id);
        $this->assertEquals($entities[1]->embed->bidirectional[0]->id, $found[1]->embed->bidirectional[0]->id);
        $this->assertEquals($entities[1]->embed->bidirectional[1]->id, $found[1]->embed->bidirectional[1]->id);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed[0]->id, $found[1]->embed->bidirectionalInversed[0]->id);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed[1]->id, $found[1]->embed->bidirectionalInversed[1]->id);

        $found = $this->_em->createQuery($dql)->getArrayResult();

        $this->assertCount(2, $found);

        $this->assertEquals($entities[0]->embed->unidirectional[0]->id, $found[0]['embed.unidirectional'][0]['id']);
        $this->assertEquals($entities[0]->embed->unidirectional[1]->id, $found[0]['embed.unidirectional'][1]['id']);
        $this->assertEquals($entities[0]->embed->bidirectional[0]->id, $found[0]['embed.bidirectional'][0]['id']);
        $this->assertEquals($entities[0]->embed->bidirectional[1]->id, $found[0]['embed.bidirectional'][1]['id']);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed[0]->id, $found[0]['embed.bidirectionalInversed'][0]['id']);
        $this->assertEquals($entities[0]->embed->bidirectionalInversed[1]->id, $found[0]['embed.bidirectionalInversed'][1]['id']);

        $this->assertEquals($entities[1]->embed->unidirectional[0]->id, $found[1]['embed.unidirectional'][0]['id']);
        $this->assertEquals($entities[1]->embed->unidirectional[1]->id, $found[1]['embed.unidirectional'][1]['id']);
        $this->assertEquals($entities[1]->embed->bidirectional[0]->id, $found[1]['embed.bidirectional'][0]['id']);
        $this->assertEquals($entities[1]->embed->bidirectional[1]->id, $found[1]['embed.bidirectional'][1]['id']);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed[0]->id, $found[1]['embed.bidirectionalInversed'][0]['id']);
        $this->assertEquals($entities[1]->embed->bidirectionalInversed[1]->id, $found[1]['embed.bidirectionalInversed'][1]['id']);

    }
}

/**
 * @Entity
 */
class BidirectionalOne2ManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @OneToMany(targetEntity="DDCEmbeddableManyToOne", mappedBy="embed.bidirectional")
     */
    public $property;
}

/**
 * @Entity
 */
class UnidirectionalOne2ManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @Embeddable
 */
class DDCEmbedManyToOne
{
    const CLASSNAME = __CLASS__;

    /**
     * @ManyToOne(targetEntity="UnidirectionalOne2ManyEntity")
     */
    public $unidirectional;

    /**
     * @ManyToOne(targetEntity = "BidirectionalOne2ManyEntity", inversedBy = "property")
     */
    public $bidirectional;
}

/**
 * @Entity
 */
class DDCEmbeddableManyToOne
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Embedded(class = "DDCEmbedManyToOne", columnPrefix = false)
     */
    public $embed;

    public function __construct()
    {
        $this->embed = new DDCEmbedManyToOne();
    }
}

/**
 * @Entity
 */
class ManyToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @ManyToOne(targetEntity="DDCEmbeddableOneToMany", inversedBy="embed.entities")
     */
    public $property;
}


/**
 * @Embeddable()
 */
class DDCEmbedOneToMany
{
    const CLASSNAME = __CLASS__;

    /**
     * @OneToMany(targetEntity="ManyToOneEntity", mappedBy="property")
     */
    public $entities;
}

/**
 * @Entity
 */
class DDCEmbeddableOneToMany
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Embedded(class = "DDCEmbedOneToMany", columnPrefix = false)
     */
    public $embed;

    public function __construct()
    {
        $this->embed = new DDCEmbedOneToMany();
    }
}

/**
 * @Entity
 */
class UnidirectionalManyToManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @Entity
 */
class BidirectionalManyToManyEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @ManyToMany(targetEntity="DDCEmbeddableManyToMany", mappedBy="embed.bidirectional")
     */
    public $property;

    /**
     * @var int
     * @ManyToMany(targetEntity="DDCEmbeddableManyToMany", inversedBy="embed.bidirectionalInversed")
     */
    public $propertyInversed;
}

/**
 * @Embeddable()
 */
class DDCEmbedManyToMany
{
    const CLASSNAME = __CLASS__;

    /**
     * @ManyToMany(targetEntity="UnidirectionalManyToManyEntity")
     */
    public $unidirectional;

    /**
     * @ManyToMany(targetEntity="BidirectionalManyToManyEntity", inversedBy="property")
     */
    public $bidirectional;

    /**
     * @ManyToMany(targetEntity="BidirectionalManyToManyEntity", mappedBy="propertyInversed")
     */
    public $bidirectionalInversed;
}

/**
 * @Entity
 */
class DDCEmbeddableManyToMany
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Embedded(class = "DDCEmbedManyToMany", columnPrefix = false)
     */
    public $embed;

    /**
     * DDC3480Vacancy constructor.
     */
    public function __construct()
    {
        $this->embed = new DDCEmbedManyToMany();
    }
}

/**
 * @Entity
 */
class UnidirectionalOneToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @Entity
 */
class BidirectionalOneToOneEntity
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @OneToOne(targetEntity="DDCEmbeddableOneToOne", mappedBy="embed.bidirectional")
     */
    public $property;

    /**
     * @var int
     * @OneToOne(targetEntity="DDCEmbeddableOneToOne", inversedBy="embed.bidirectionalInversed")
     */
    public $propertyInversed;
}

/**
 * @Embeddable()
 */
class DDCEmbedOneToOne
{
    const CLASSNAME = __CLASS__;

    /**
     * @OneToOne(targetEntity="UnidirectionalOneToOneEntity")
     */
    public $unidirectional;

    /**
     * @OneToOne(targetEntity="BidirectionalOneToOneEntity", inversedBy="property")
     */
    public $bidirectional;

    /**
     * @OneToOne(targetEntity="BidirectionalOneToOneEntity", mappedBy="propertyInversed")
     */
    public $bidirectionalInversed;
}

/**
 * @Entity
 */
class DDCEmbeddableOneToOne
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Embedded(class = "DDCEmbedOneToOne", columnPrefix = false)
     */
    public $embed;

    /**
     * DDC3480Vacancy constructor.
     */
    public function __construct()
    {
        $this->embed = new DDCEmbedOneToOne();
    }
}
