<?php
/* SDK for  nForumApi
 *   
 *  author:rainsun
 *  data:201203
 */


/*
 * How to use?
 *
 * $w = new nforumapi(  );
 * $w->setUser( 'username' , 'password' );
 * print_r($w->****(***));
 *
*/


class nforumapi 
{
	function __construct( $akey , $skey = '' ) 
	{
		$this->akey = $akey;
		$this->base = '';		// **Please modify this**
		$this->curl = curl_init();
		curl_setopt( $this->curl , CURLOPT_RETURNTRANSFER, true); 
	}

	function setUser( $name , $pass ) 
	{
		$this->user['name'] = $name;
		$this->user['pass']  = $pass;
		curl_setopt( $this->curl , CURLOPT_USERPWD , "$name:$pass" );
	}

	function public_queryid( $id )
	{
		return $this->call_method( 'user' , 'query' , $id );
	}

	function public_queryboard( $name )
	{
		return $this->call_method( 'board' , '' , $name );	
	}
	
	function call_method( $method , $action , $args = '' ) 
	{
		
		curl_setopt( $this->curl , CURLOPT_POSTFIELDS , join( '&' , $this->postdata ) );

		$enter = $action?'/':'';
		$url = $this->base . $method . '/' . $action . $enter . $args . '.json?appkey=' . $this->akey ;
		curl_setopt($this->curl , CURLOPT_URL , $url );
		
		return json_decode(curl_exec( $this->curl ) , true);
		
	}
	
	function __destruct ()
	{
		curl_close($this->curl);
	}
}
?>
