'use strict';

const { productionManifest, validateManifest } = require( './package' );

describe( 'production package manifest', () => {
	test( 'includes only the three approved readable block source files', () => {
		const entries = productionManifest();
		const names = entries.map( ( entry ) => entry.relativePath );
		const sourceNames = names.filter( ( name ) =>
			name.startsWith( 'src/' )
		);

		const nonPhpSourceNames = sourceNames.filter(
			( name ) => ! name.endsWith( '.php' )
		);
		[
			'src/blocks/cta/block.json',
			'src/blocks/cta/editor.scss',
			'src/blocks/cta/index.js',
		].forEach( ( sourceName ) => {
			expect( names ).toContain( sourceName );
		} );
		expect( nonPhpSourceNames ).toEqual( [
			'src/blocks/cta/block.json',
			'src/blocks/cta/editor.scss',
			'src/blocks/cta/index.js',
		] );
		expect( sourceNames ).not.toEqual(
			expect.arrayContaining( [
				'src/blocks/cta/index.js.map',
				'src/blocks/cta/index.test.js',
				'src/blocks/cta/serialization.test.js',
			] )
		);
		expect( () => validateManifest( entries ) ).not.toThrow();
	} );

	test( 'rejects forbidden source maps and test files', () => {
		[
			'src/blocks/cta/index.js.map',
			'src/blocks/cta/index.test.js',
			'src/blocks/cta/serialization.test.js',
		].forEach( ( relativePath ) => {
			const entries = productionManifest();
			entries.push( { relativePath, absolutePath: relativePath } );

			expect( () => validateManifest( entries ) ).toThrow(
				'Production package contains forbidden entries'
			);
			expect( () => validateManifest( entries ) ).toThrow( relativePath );
		} );
	} );

	test( 'rejects an unrelated non-PHP source file', () => {
		const entries = productionManifest();
		entries.push( {
			relativePath: 'src/blocks/cta/notes.txt',
			absolutePath: 'src/blocks/cta/notes.txt',
		} );

		expect( () => validateManifest( entries ) ).toThrow(
			'Production package contains an unapproved non-PHP source file'
		);
	} );
} );
