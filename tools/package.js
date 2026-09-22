'use strict';
/* eslint-disable no-console */

const crypto = require( 'crypto' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const yazl = require( 'yazl' );

const ROOT = path.resolve( __dirname, '..' );
const PACKAGE_VERSION = require( path.join( ROOT, 'package.json' ) ).version;

function compareStrings( first, second ) {
	if ( first === second ) {
		return 0;
	}

	return first < second ? -1 : 1;
}

const FIXED_DATE = new Date( '2000-01-01T00:00:00.000Z' );
const ALLOWED_NON_PHP_SOURCE_ENTRIES = [
	'src/blocks/cta/block.json',
	'src/blocks/cta/editor.scss',
	'src/blocks/cta/index.js',
];
const REQUIRED_ENTRIES = [
	'CHANGELOG.md',
	'LICENSE',
	'README.md',
	'assets/admin/settings.js',
	'assets/admin/settings.css',
	'assets/public/cta.css',
	'build/blocks/cta/block.json',
	'build/blocks/cta/index.asset.php',
	'build/blocks/cta/index.css',
	'build/blocks/cta/index-rtl.css',
	'build/blocks/cta/index.js',
	'languages/opennow.pot',
	'src/blocks/cta/block.json',
	'src/blocks/cta/editor.scss',
	'src/blocks/cta/index.js',
	'opennow.php',
	'readme.txt',
	'uninstall.php',
];
const FORBIDDEN_PARTS = [
	'.github/',
	'build/blocks/cta/index.js.map',
	'composer.json',
	'composer.lock',
	'dist/',
	'docs/',
	'node_modules/',
	'package-lock.json',
	'package.json',
	'phpcs.xml.dist',
	'phpunit.integration.xml.dist',
	'phpunit.xml.dist',
	'plugin-concept.md',
	'src/blocks/cta/index.js.map',
	'src/blocks/cta/index.test.js',
	'src/blocks/cta/serialization.test.js',
	'tests/',
	'tools/',
	'vendor/',
];

function addTree( entries, directory, prefix, predicate ) {
	if ( ! fs.existsSync( directory ) ) {
		return;
	}
	fs.readdirSync( directory, { withFileTypes: true } )
		.sort( ( first, second ) => compareStrings( first.name, second.name ) )
		.forEach( ( entry ) => {
			const absolutePath = path.join( directory, entry.name );
			const relativePath = `${ prefix }${ entry.name }`;
			if ( entry.isDirectory() ) {
				addTree(
					entries,
					absolutePath,
					`${ relativePath }/`,
					predicate
				);
				return;
			}
			if ( predicate( relativePath ) ) {
				entries.set( relativePath, absolutePath );
			}
		} );
}

function productionManifest() {
	const entries = new Map();
	const addFile = ( relativePath ) =>
		entries.set( relativePath, path.join( ROOT, relativePath ) );

	REQUIRED_ENTRIES.forEach( addFile );
	addTree( entries, path.join( ROOT, 'src' ), 'src/', ( relativePath ) =>
		relativePath.endsWith( '.php' )
	);

	return Array.from( entries.keys() )
		.sort( compareStrings )
		.map( ( relativePath ) => ( {
			relativePath,
			absolutePath: entries.get( relativePath ),
		} ) );
}

function validateManifest( entries ) {
	const names = entries.map( ( entry ) => entry.relativePath );
	REQUIRED_ENTRIES.forEach( ( required ) => {
		if ( ! names.includes( required ) ) {
			throw new Error(
				`Production package is missing required entry: ${ required }`
			);
		}
		if ( ! fs.existsSync( path.join( ROOT, required ) ) ) {
			throw new Error(
				`Required production file does not exist: ${ required }`
			);
		}
	} );

	const forbidden = names.filter( ( name ) =>
		FORBIDDEN_PARTS.some(
			( part ) => name === part || name.startsWith( part )
		)
	);
	if ( forbidden.length ) {
		throw new Error(
			`Production package contains forbidden entries: ${ forbidden.join(
				', '
			) }`
		);
	}

	const unapprovedSourceFiles = names.filter(
		( name ) =>
			name.startsWith( 'src/' ) &&
			! name.endsWith( '.php' ) &&
			! ALLOWED_NON_PHP_SOURCE_ENTRIES.includes( name )
	);
	if ( unapprovedSourceFiles.length ) {
		throw new Error(
			`Production package contains an unapproved non-PHP source file: ${ unapprovedSourceFiles.join(
				', '
			) }`
		);
	}
}

function writeZip( outputPath, entries ) {
	return new Promise( ( resolve, reject ) => {
		const zipfile = new yazl.ZipFile();
		const output = fs.createWriteStream( outputPath );
		let settled = false;
		const fail = ( error ) => {
			if ( settled ) {
				return;
			}
			settled = true;
			reject( error );
		};
		output.on( 'error', fail );
		zipfile.outputStream.on( 'error', fail );
		output.on( 'close', () => {
			if ( ! settled ) {
				settled = true;
				resolve();
			}
		} );
		zipfile.outputStream.pipe( output );

		try {
			entries.forEach( ( entry ) => {
				zipfile.addFile(
					entry.absolutePath,
					`opennow/${ entry.relativePath }`,
					{
						compress: true,
						mtime: FIXED_DATE,
						mode: 0o100664,
					}
				);
			} );
			zipfile.end();
		} catch ( error ) {
			fail( error );
		}
	} );
}

function digest( filePath ) {
	return crypto
		.createHash( 'sha256' )
		.update( fs.readFileSync( filePath ) )
		.digest( 'hex' );
}

async function createPackage( outputPath ) {
	const entries = productionManifest();
	validateManifest( entries );
	fs.mkdirSync( path.dirname( outputPath ), { recursive: true } );
	await writeZip( outputPath, entries );
	return outputPath;
}

async function main() {
	const check = process.argv.includes( '--check' );
	const outputIndex = process.argv.indexOf( '--output' );
	const outputPath =
		outputIndex !== -1 && process.argv[ outputIndex + 1 ]
			? path.resolve( ROOT, process.argv[ outputIndex + 1 ] )
			: path.join( ROOT, 'dist', `opennow-${ PACKAGE_VERSION }.zip` );

	if ( ! check ) {
		await createPackage( outputPath );
		console.log(
			`Created deterministic production package: ${ path.relative(
				ROOT,
				outputPath
			) }`
		);
		return;
	}

	const temporaryDirectory = fs.mkdtempSync(
		path.join( os.tmpdir(), 'opennow-package-' )
	);
	const first = path.join( temporaryDirectory, 'first.zip' );
	const second = path.join( temporaryDirectory, 'second.zip' );
	try {
		await createPackage( first );
		await createPackage( second );
		if ( digest( first ) !== digest( second ) ) {
			throw new Error( 'Production package is not reproducible.' );
		}
		console.log(
			`Production package is reproducible: ${ digest( first ) }`
		);
	} finally {
		fs.rmSync( temporaryDirectory, { recursive: true, force: true } );
	}
}

if ( require.main === module ) {
	main().catch( ( error ) => {
		console.error( error.message );
		process.exitCode = 1;
	} );
}

module.exports = {
	ALLOWED_NON_PHP_SOURCE_ENTRIES,
	FORBIDDEN_PARTS,
	REQUIRED_ENTRIES,
	productionManifest,
	validateManifest,
};
