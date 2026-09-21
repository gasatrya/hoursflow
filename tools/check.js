'use strict';
/* eslint-disable no-console */

const path = require( 'path' );
const { spawnSync } = require( 'child_process' );

const ROOT = path.resolve( __dirname, '..' );
const CHECKS = [
	'audit:production',
	'test:js',
	'lint:js',
	'lint:style',
	'format:check',
	'build:check',
	'i18n:pot:check',
	'package:check',
];

function npmCommand( script ) {
	const npmCli = process.env.npm_execpath;
	if ( npmCli ) {
		return {
			command: process.execPath,
			argumentsList: [ npmCli, 'run', '--silent', script ],
		};
	}

	return {
		command: process.platform === 'win32' ? 'npm.cmd' : 'npm',
		argumentsList: [ 'run', '--silent', script ],
	};
}

function printOutput( output ) {
	if ( output && output.trim() ) {
		process.stderr.write( `${ output.trimEnd() }\n` );
	}
}

function runCheck( script ) {
	const { command, argumentsList } = npmCommand( script );
	const result = spawnSync( command, argumentsList, {
		cwd: ROOT,
		encoding: 'utf8',
		maxBuffer: 10 * 1024 * 1024,
	} );

	if ( result.status === 0 ) {
		console.log( `PASS ${ script }` );
		return true;
	}

	console.error( `FAIL ${ script }` );
	printOutput( result.stdout );
	printOutput( result.stderr );
	if ( result.error ) {
		console.error( result.error.message );
	} else if ( result.signal ) {
		console.error( `Command terminated by ${ result.signal }.` );
	}
	return false;
}

function main() {
	for ( const script of CHECKS ) {
		if ( ! runCheck( script ) ) {
			process.exitCode = 1;
			return;
		}
	}

	console.log( `All ${ CHECKS.length } checks passed.` );
}

main();
