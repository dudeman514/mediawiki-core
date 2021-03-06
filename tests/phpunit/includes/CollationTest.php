<?php
class CollationTest extends MediaWikiLangTestCase {
	protected function setUp() {
		parent::setUp();
		if ( !wfDl( 'intl' ) ) {
			$this->markTestSkipped( 'These tests require intl extension' );
		}
	}

	/**
	 * Test to make sure, that if you
	 * have "X" and "XY", the binary
	 * sortkey also has "X" being a
	 * prefix of "XY". Our collation
	 * code makes this assumption.
	 *
	 * @param $lang String Language code for collator
	 * @param $base String Base string
	 * @param $extended String String containing base as a prefix.
	 *
	 * @dataProvider prefixDataProvider
	 */
	function testIsPrefix( $lang, $base, $extended ) {
		$cp = Collator::create( $lang );
		$cp->setStrength( Collator::PRIMARY );
		$baseBin = $cp->getSortKey( $base );
		// Remove sortkey terminator
		$baseBin = rtrim( $baseBin, "\0" );
		$extendedBin = $cp->getSortKey( $extended );
		$this->assertStringStartsWith( $baseBin, $extendedBin, "$base is not a prefix of $extended" );
	}

	function prefixDataProvider() {
		return array(
			array( 'en', 'A', 'AA' ),
			array( 'en', 'A', 'AAA' ),
			array( 'en', 'Д', 'ДЂ' ),
			array( 'en', 'Д', 'ДA' ),
			// 'Ʒ' should expand to 'Z ' (note space).
			array( 'fi', 'Z', 'Ʒ' ),
			// 'Þ' should expand to 'th'
			array( 'sv', 't', 'Þ' ),
			// Javanese is a limited use alphabet, so should have 3 bytes
			// per character, so do some tests with it.
			array( 'en', 'ꦲ', 'ꦲꦤ' ),
			array( 'en', 'ꦲ', 'ꦲД' ),
			array( 'en', 'A', 'Aꦲ' ),
		);
	}

	/**
	 * Opposite of testIsPrefix
	 *
	 * @dataProvider notPrefixDataProvider
	 */
	function testNotIsPrefix( $lang, $base, $extended ) {
		$cp = Collator::create( $lang );
		$cp->setStrength( Collator::PRIMARY );
		$baseBin = $cp->getSortKey( $base );
		// Remove sortkey terminator
		$baseBin = rtrim( $baseBin, "\0" );
		$extendedBin = $cp->getSortKey( $extended );
		$this->assertStringStartsNotWith( $baseBin, $extendedBin, "$base is a prefix of $extended" );
	}

	function notPrefixDataProvider() {
		return array(
			array( 'en', 'A', 'B' ),
			array( 'en', 'AC', 'ABC' ),
			array( 'en', 'Z', 'Ʒ' ),
			array( 'en', 'A', 'ꦲ' ),
		);
	}

	/**
	 * Test correct first letter is fetched.
	 *
	 * @param $collation String Collation name (aka uca-en)
	 * @param $string String String to get first letter of
	 * @param $firstLetter String Expected first letter.
	 *
	 * @dataProvider firstLetterProvider
	 */
	function testGetFirstLetter( $collation, $string, $firstLetter ) {
		$col = Collation::factory( $collation );
		$this->assertEquals( $firstLetter, $col->getFirstLetter( $string ) );
	}

	function firstLetterProvider() {
		return array(
			array( 'uppercase', 'Abc', 'A' ),
			array( 'uppercase', 'abc', 'A' ),
			array( 'identity', 'abc', 'a' ),
			array( 'uca-en', 'abc', 'A' ),
			array( 'uca-en', ' ', ' ' ),
			array( 'uca-en', 'Êveryone', 'E' ),
			array( 'uca-vi', 'Êveryone', 'Ê' ),
			// Make sure thorn is not a first letter.
			array( 'uca-sv', 'The', 'T' ),
			array( 'uca-sv', 'Å', 'Å' ),
			array( 'uca-hu', 'dzsdo', 'Dzs' ),
			array( 'uca-hu', 'dzdso', 'Dz' ),
			array( 'uca-hu', 'CSD', 'Cs' ),
			array( 'uca-root', 'CSD', 'C' ),
			array( 'uca-fi', 'Ǥ', 'G' ),
			array( 'uca-fi', 'Ŧ', 'T' ),
			array( 'uca-fi', 'Ʒ', 'Z' ),
			array( 'uca-fi', 'Ŋ', 'N' ),
		);
	}
}
