'use strict';
/* eslint-disable no-console */

const crypto = require( 'crypto' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const { makePot } = require( './make-pot' );

const ROOT = path.resolve( __dirname, '..' );
const EXPECTED = path.join( ROOT, 'languages', 'opennow.pot' );

function digest( filePath ) {
	return crypto
		.createHash( 'sha256' )
		.update( fs.readFileSync( filePath ) )
		.digest( 'hex' );
}

function main() {
	const temporaryDirectory = fs.mkdtempSync(
		path.join( os.tmpdir(), 'opennow-pot-' )
	);
	const first = path.join( temporaryDirectory, 'first.pot' );
	const second = path.join( temporaryDirectory, 'second.pot' );

	try {
		makePot( first );
		makePot( second );
		if ( digest( first ) !== digest( second ) ) {
			throw new Error( 'POT generation is not reproducible.' );
		}
		if (
			! fs.existsSync( EXPECTED ) ||
			digest( first ) !== digest( EXPECTED )
		) {
			throw new Error(
				'languages/opennow.pot is out of date. Run npm run i18n:pot.'
			);
		}
		console.log(
			'POT generation is reproducible and the checked-in file is current.'
		);
	} finally {
		fs.rmSync( temporaryDirectory, { recursive: true, force: true } );
	}
}

try {
	main();
} catch ( error ) {
	console.error( error.message );
	process.exitCode = 1;
}
