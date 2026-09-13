'use strict';
/* eslint-disable no-console */

const crypto = require( 'crypto' );
const fs = require( 'fs' );
const path = require( 'path' );
const { spawnSync } = require( 'child_process' );

const ROOT = path.resolve( __dirname, '..' );
const BUILD_DIRECTORY = path.join( ROOT, 'build' );

function snapshot( directory ) {
	const files = new Map();
	if ( ! fs.existsSync( directory ) ) {
		return files;
	}

	function visit( currentDirectory ) {
		fs.readdirSync( currentDirectory, { withFileTypes: true } )
			.sort( ( first, second ) =>
				first.name.localeCompare( second.name )
			)
			.forEach( ( entry ) => {
				const absolutePath = path.join( currentDirectory, entry.name );
				if ( entry.isDirectory() ) {
					visit( absolutePath );
					return;
				}
				const relativePath = path
					.relative( directory, absolutePath )
					.split( path.sep )
					.join( '/' );
				const digest = crypto
					.createHash( 'sha256' )
					.update( fs.readFileSync( absolutePath ) )
					.digest( 'hex' );
				files.set( relativePath, digest );
			} );
	}

	visit( directory );
	return files;
}

function sameSnapshot( first, second ) {
	if ( first.size !== second.size ) {
		return false;
	}
	for ( const [ file, digest ] of first ) {
		if ( second.get( file ) !== digest ) {
			return false;
		}
	}
	return true;
}

function runBuild() {
	const npmCli = process.env.npm_execpath;
	let command = process.platform === 'win32' ? 'npm.cmd' : 'npm';
	let argumentsList = [ 'run', 'build' ];
	if ( npmCli ) {
		command = process.execPath;
		argumentsList = [ npmCli, 'run', 'build' ];
	}
	const result = spawnSync( command, argumentsList, {
		cwd: ROOT,
		stdio: 'inherit',
	} );
	if ( result.error ) {
		throw result.error;
	}
	if ( result.status !== 0 ) {
		throw new Error( `Build failed with exit code ${ result.status }.` );
	}
}

function main() {
	const before = snapshot( BUILD_DIRECTORY );

	runBuild();
	const first = snapshot( BUILD_DIRECTORY );
	runBuild();
	const second = snapshot( BUILD_DIRECTORY );

	if ( ! sameSnapshot( first, second ) ) {
		throw new Error( 'Generated build output is not reproducible.' );
	}
	if ( ! sameSnapshot( before, first ) ) {
		throw new Error(
			'Generated build output is out of date. Run npm run build.'
		);
	}
	console.log(
		'Build output is reproducible and the checked-in files are current.'
	);
}

try {
	main();
} catch ( error ) {
	console.error( error.message );
	process.exitCode = 1;
}
