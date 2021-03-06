<?php

namespace Netgen\TagsBundle\Tests\Core\Search\Solr\Query\Common\CriterionVisitor\Tags;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LocationId;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\Core\Persistence\Legacy\Content\Type\Handler;
use eZ\Publish\Core\Search\Common\FieldNameResolver;
use eZ\Publish\Core\Search\Common\FieldValueMapper\MultipleStringMapper;
use eZ\Publish\SPI\Search\FieldType;
use EzSystems\EzPlatformSolrSearchEngine\Tests\Search\TestCase;
use Netgen\TagsBundle\API\Repository\Values\Content\Query\Criterion;
use Netgen\TagsBundle\Core\Search\Solr\Query;

class TagKeywordTest extends TestCase
{
    /**
     * @var \eZ\Publish\Core\Search\Common\FieldNameResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldNameResolver;

    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Type\Handler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentTypeHandler;

    /**
     * @var \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags\TagKeyword
     */
    protected $visitor;

    public function setUp()
    {
        $this->fieldNameResolver = $this->createMock(FieldNameResolver::class);

        $this->contentTypeHandler = $this->createMock(Handler::class);

        $this->visitor = new Query\Common\CriterionVisitor\Tags\TagKeyword(
            $this->fieldNameResolver,
            new MultipleStringMapper(),
            $this->contentTypeHandler,
            'eztags',
            'tag_keywords'
        );
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags\TagKeyword::canVisit
     */
    public function testCanVisit()
    {
        $criterion = new Criterion\TagKeyword(Operator::IN, array('tag1', 'tag2'));
        self::assertTrue($this->visitor->canVisit($criterion));
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags\TagKeyword::canVisit
     */
    public function testCanVisitReturnsFalse()
    {
        $criterion = new LocationId(array('tag1', 'tag2'));
        self::assertFalse($this->visitor->canVisit($criterion));
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags\TagKeyword::visit
     * @covers \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags::getSearchFields
     */
    public function testVisit()
    {
        $criterion = new Criterion\TagKeyword(Operator::IN, array('tag1', 'tag2'), 'tags_field');

        $this->fieldNameResolver
            ->expects($this->once())
            ->method('getFieldTypes')
            ->with(
                $this->equalTo($criterion),
                $this->equalTo('tags_field'),
                'eztags',
                'tag_keywords'
            )
            ->will(
                $this->returnValue(
                    array(
                        'tags_field_s' => new FieldType\MultipleStringField(),
                        'tags_field2_s' => new FieldType\MultipleStringField(),
                    )
                )
            );

        $this->contentTypeHandler
            ->expects($this->never())
            ->method('getSearchableFieldMap');

        $this->assertEquals(
            '(tags_field_s:"tag1" OR tags_field_s:"tag2" OR tags_field2_s:"tag1" OR tags_field2_s:"tag2")',
            $this->visitor->visit($criterion)
        );
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags\TagKeyword::visit
     * @covers \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags::getSearchFields
     */
    public function testVisitWithoutTarget()
    {
        $criterion = new Criterion\TagKeyword(Operator::IN, array('tag1', 'tag2'));

        $this->contentTypeHandler
            ->expects($this->once())
            ->method('getSearchableFieldMap')
            ->will(
                $this->returnValue(
                    array(
                        'news' => array(
                            'tags_field' => array(
                                'field_type_identifier' => 'eztags',
                            ),
                        ),
                        'article' => array(
                            'tags_field2' => array(
                                'field_type_identifier' => 'eztags',
                            ),
                        ),
                    )
                )
            );

        $this->fieldNameResolver
            ->expects($this->at(0))
            ->method('getFieldTypes')
            ->with(
                $this->equalTo($criterion),
                $this->equalTo('tags_field'),
                'eztags',
                'tag_keywords'
            )
            ->will(
                $this->returnValue(
                    array(
                        'news_tags_field_s' => new FieldType\MultipleStringField(),
                    )
                )
            );

        $this->fieldNameResolver
            ->expects($this->at(1))
            ->method('getFieldTypes')
            ->with(
                $this->equalTo($criterion),
                $this->equalTo('tags_field2'),
                'eztags',
                'tag_keywords'
            )
            ->will(
                $this->returnValue(
                    array(
                        'article_tags_field2_s' => new FieldType\MultipleStringField(),
                    )
                )
            );

        $this->assertEquals(
            '(news_tags_field_s:"tag1" OR news_tags_field_s:"tag2" OR article_tags_field2_s:"tag1" OR article_tags_field2_s:"tag2")',
            $this->visitor->visit($criterion)
        );
    }

    /**
     * @covers \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags\TagKeyword::visit
     * @covers \Netgen\TagsBundle\Core\Search\Solr\Query\Common\CriterionVisitor\Tags::getSearchFields
     * @expectedException \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function testVisitThrowsInvalidArgumentException()
    {
        $criterion = new Criterion\TagKeyword(Operator::IN, array('tag1', 'tag2'), 'tags_field');

        $this->fieldNameResolver
            ->expects($this->once())
            ->method('getFieldTypes')
            ->with(
                $this->equalTo($criterion),
                $this->equalTo('tags_field'),
                'eztags',
                'tag_keywords'
            )
            ->will($this->returnValue(array()));

        $this->visitor->visit($criterion);
    }
}
