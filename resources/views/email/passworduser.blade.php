<br />
<table width="90%" align="center" cellspacing="0" cellpadding="0">
  <style>
    <!--
    a:hover {
    	color : #0084b4 ;
    	text-decoration : none;
    }
    a.gold:link, a:visited {
    	color : #0084b4 ;
    	text-decoration : none;
    }
    a.gold:hover {
    	color : #959595 ;
    	text-decoration : none;
    }
    -->
    <!--
    @import url(http://fonts.googleapis.com/css?family=Raleway:800,500);
    -->
  </style>

  <tr>
    <td width="50%" height="13">
        <img src="https://asielnetwork.com/assets/images/Logo.png" alt="" width="200px">
    </td>

    <td width="50%">
      <p align="right" style="margin-bottom: 6px; margin-top: 15px"></p>
    </td>
  </tr>

  <tr>
    <td colspan="2">
      <hr style="height: 1px; border: none; background-color: #959595" />
    </td>
  </tr>

  <tr>
    <td colspan="2">
        <p style="
          font-size: 14px;
          color: #0c0b0b;
          font-family: 'Raleway', Verdana, Arial, Helvetica, sans-serif;
          margin-bottom: 10px;
          margin-top: 10px;
          line-height: 1.5;
          font-weight: 500;
        ">
            <!-- "288 Pellentesque VW." replace to your address 1 -->
            ¡Hola! <b{{ $mailData['customer_name'] }}</b>, tu nueva contraseña es:
            <br />
        </p>

      <p
        style="
          font-size: 20px;
          color: #959595;
          font-family: 'Raleway', Verdana, Arial, Helvetica, sans-serif;
          margin-bottom: 10px;
          margin-top: 10px;
          line-height: 1.5;
          font-weight: 500;
          text-align: center;
        "
      >
     {{ $mailData['password'] }}
      </p>

    </td>
  </tr>
  <tr>
    <td colspan="2" style="padding: 20px;">
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
        <a href={{ $mailData['url'] }}" class=""
          style="
            background-color: #215EE9;
            display: inline-block;
            padding: 20px;
            border-radius: 10px;
            color: #FFF;
            text-decoration: none;
            text-transform: uppercase;
          "
        >Ingresar aquí</a>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="padding: 20px;">
    </td>
  </tr>
  <tr>
    <td colspan="2">
        <p
            style="
            font-size: 11px;
            font-family: Arial, Helvetica, sans-serif;
            color: #cecece;
            margin-top: 15px;
            "
        >
            Nota: El presente correo ha sido generado y enviado en forma automática.
            Por favor no responder el correo.
        </p>
    </td>
  </tr>
</table>
