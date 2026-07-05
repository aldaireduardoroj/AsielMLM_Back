<!DOCTYPE html>
<html>
<head>
    <title></title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20mm;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 5px;
            line-height: 1;
        }
        p {
            text-align: justify;
            margin: 0;
            color: #888888;
        }
        b{
            color: #000000;
        }
        h3{
            color: #888888;
            font-size: 18px;
            margin-top: 5px;
        }
        hr{
            border-color: #DDDDDD;
            border-top: 0;
        }
        footer {
            position: fixed; /* Fija el pie de página en cada página */
            bottom: 0px;     /* Lo coloca en la parte inferior */
            left: 0px;
            right: 0px;
            height: 80px;    /* Altura del pie de página */

            /** Estilos adicionales **/
            text-align: center;
            color: #555;
            font-size: 12px;
            line-height: 2; /* Alinea verticalmente el texto */
            padding-left: 30px;
            padding-right: 30px;
        }
        h2{
            margin-top: 5px;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <main>

        <table width="100%">
            <tbody>
                <tr>
                    <td>
                        <table width="100%">
                            <tbody>
                                <tr><td>Nombre de usuario: <b>{{ $fullname }}</b></td></tr>
                                <tr><td>ID: <b>{{ $code }} - {{ $status }}</b></td></tr>
                                <tr><td>Correo: <b>{{ $email }}</b></td></tr>
                                <tr><td>Mes de cierre: <b>{{ $mes }} {{ $year }}</b></td></tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <table width="100%">
                            <tbody>
                                <tr><td rowspan="2" align="right">
                                    <h2>Vithara</h2>
                                    <h2>Impulsa tu vida</h2>
                                    <p style="text-align: right;">Negocio Multinivel</p>
                                </td></tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 10px">
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><b style="color: #888888;">Resumen de puntos y bonos logrados</b></p>
                        <hr>
                    </td>

                </tr>
                <tr>
                    <td>
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td colspan="2">Plan Actual = <b>{{ $plan }}</b></td>
                                </tr>
                                <tr><td></td></tr>
                                <tr>
                                    <td>Puntos Patrocinio:</td>
                                    <td><b>{{ $patrocinio }} puntos</b></td>
                                </tr>
                                <tr>
                                    <td>Puntos Residuales:</td>
                                    <td><b>{{ $residualTotal }} puntos</b></td>
                                </tr>
                                <tr>
                                    <td>Puntos Grupales:</td>
                                    <td><b>{{ $pointGroup }} puntos</b></td>
                                </tr>
                                <tr>
                                    <td>Puntos por plan Actual:</td>
                                    <td><b>{{ $currentPack }} puntos</b></td>
                                </tr>
                                <tr>
                                    <td>Puntos Gran total</td>
                                    <td><b>{{ $totalPoint }} puntos</b></td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding: 10px"></td>
                                </tr>
                                <tr>
                                    <td>Puntos Global de Patrocinio</td>
                                    <td><b>{{ $globalPatrocinio }} puntos</b></td>
                                </tr>
                                <tr>
                                    <td>Puntos Pionero</td>
                                    <td><b>{{ $bonoPionero }} puntos</b></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 10px">
                    </td>
                </tr>
                <tr>
                    <td>
                        <b style="color: #888888;">Rango</b>
                        <hr>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top;">
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td>Rango:</td>
                                    @if( $countRange != '' )
                                        <td><b>{{ $range }} - 1</b></td>
                                    @else
                                        <td><b>{{ $range }}</b></td>
                                    @endif
                                    
                                </tr>
                                @if( $bonoRange != '0' )
                                <tr>
                                    <td>Bono por rango:</td>
                                    <td><b>{{ $bonoRange }} PEN</b></td>
                                </tr>
                                @endif
                                <tr>
                                    <td>Bono residual x volumen:</td>
                                    <td><b>{{ $residualVolumen }} puntos</b></td>
                                </tr>
                            </tbody>
                        </table>

                    </td>
                </tr>
            </tbody>
        </table>
    </main>
    <footer>
        <hr>
        <p>Las comisiones obtenidas se depositarán a la cuenta bancaria registrado del socio.</p>
        <p>Vithara SAC</p>
        <p>Vithara@email.com</p>
    </footer>
</body>
</html>
