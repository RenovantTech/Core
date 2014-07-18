<?php

function parse_cli() {
	$argv = $_SERVER['argv'];
	$_ARGV = new ArrayObject();
	array_shift($argv);
	foreach ($argv as $arg){
		// --foo --bar=baz
		if(substr($arg,0,2) == '--'){
			$eqPos = strpos($arg,'=');
			// --foo
			if($eqPos === false){
				$key			= substr($arg,2);
				$value			= isset($_ARGV->$key) ? $_ARGV->$key : true;
				$_ARGV->$key	= $value;
			}
			// --bar=baz
			else{
				$key			= substr($arg,2,$eqPos-2);
				$value			= substr($arg,$eqPos+1);
				$_ARGV->$key	= $value;
			}
		}
		// -k=value -abc
		elseif(substr($arg,0,1) == '-'){
			// -k=value
			if(substr($arg,2,1) == '='){
				$key			= substr($arg,1,1);
				$value			= substr($arg,3);
				$_ARGV->$key	= $value;
			}
			// -abc
			else{
				$chars = str_split(substr($arg,1));
				foreach($chars as $char){
					$key			= $char;
					$value			= isset($_ARGV->$key) ? $_ARGV->$key : true;
					$_ARGV->$key	= $value;
				}
			}
		}
		// plain-arg
		else{
		}
	}
	return $_ARGV;
}

function cli_echo($msg) {
	echo $msg."\r\n";
}