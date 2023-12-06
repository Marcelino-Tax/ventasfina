<?php
require_once '../../conexion.php';
require_once 'fpdf/fpdf.php';

function convertirNumeroALetras($numero)
{
    $unidad = array('Cero', 'Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco', 'Seis', 'Siete', 'Ocho', 'Nueve');
    $decena = array('', '', 'Veinte', 'Treinta', 'Cuarenta', 'Cincuenta', 'Sesenta', 'Setenta', 'Ochenta', 'Noventa');
    $centena = array('', 'Cien', 'Doscientos', 'Trescientos', 'Cuatrocientos', 'Quinientos', 'Seiscientos', 'Setecientos', 'Ochocientos', 'Novecientos');

    if ($numero == 0) {
        return $unidad[0];
    }

    $negativo = ($numero < 0);
    $numero = abs($numero);

    $letras = '';

    if ($numero >= 1000000) {
        $millones = floor($numero / 1000000);
        $letras .= ($millones == 1) ? 'Un Millón ' : convertirNumeroALetras($millones) . ' Millones ';
        $numero %= 1000000;
    }

    if ($numero >= 1000) {
        $miles = floor($numero / 1000);
        $letras .= ($miles == 1) ? 'Mil ' : convertirNumeroALetras($miles) . ' Mil ';
        $numero %= 1000;
    }

    if ($numero >= 100) {
        $centenas = floor($numero / 100);
        $letras .= $centena[$centenas] . ' ';
        $numero %= 100;
    }
    if ($numero >= 10 && $numero < 20) {
        $letras .= 'Dieci';
        $unidad = $numero % 10;
        if ($unidad > 0) {
            $letras .= ucfirst(strtolower($unidad));
        }
    } else {
        $decenas = floor($numero / 10);
        $unidad = $numero % 10;
        $letras .= ucfirst(strtolower($decena[$decenas])); // ucfirst para hacer la primera letra mayúscula
        if ($unidad > 0) {
            $letras .= ' y ' . strtolower($unidad);
        }
    }

    if ($negativo) {
        $letras = 'Menos ' . $letras;
    }

    return trim($letras);
}

$pdf = new FPDF('P', 'cm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(1, 2, 2);
$pdf->SetTitle("Ventas");
$pdf->SetFont('Arial', 'B', 14);

$id = $_GET['v'];
$idcliente = $_GET['cl'];
date_default_timezone_set('America/Guatemala');
$config = mysqli_query($conexion, "SELECT * FROM configuracion");
$datos = mysqli_fetch_assoc($config);
$clientes = mysqli_query($conexion, "SELECT * FROM cliente WHERE idcliente = $idcliente");
$datosC = mysqli_fetch_assoc($clientes);
$ventas = mysqli_query($conexion, "SELECT d.*, p.codproducto, p.descripcion FROM detalle_venta d INNER JOIN producto p ON d.id_producto = p.codproducto WHERE d.id_venta = $id");



// Obtener la fecha y hora actual
$fechaHoraActual = date("d-m-Y H:i:s");

// Almacenar la fecha actual en el archivo
file_put_contents('ultima_fecha.txt', $fechaHoraActual);


$pdf->Cell(20, 0.7, utf8_decode($datos['nombre']), 0, 0.2, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(20, 0.3, utf8_decode("CopyCentro Online: "), 0, 0.01, 'C');
$pdf->Cell(20, 0.3, 'Direccion: ' . utf8_decode($datos['direccion']), 0, 0.1, 'C');
$pdf->Cell(20, 0.3, 'Telefono: '. utf8_decode($datos['telefono']), 0, 0.1, 'C');
$pdf->Cell(20, 0.3, 'Correo: '. utf8_decode($datos['email']), 0, 0.5, 'C');
$pdf->image("../../assets/img/libro.png", 18, 0.5, 2, 1.9, 'PNG');
$pdf->SetFont('Arial', '', 8);



file_put_contents('ultima_fecha.txt', $fechaHoraActual);
$pdf->Ln(0.2);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(19, 0.8, "Datos del cliente", 1, 1, 'C', 1);
$pdf->Ln(0.3);
$pdf->SetTextColor(0, 0, 0);

// Contenido de la tabla
$pdf->SetFont('Arial', '', 10);


// Combinar celdas en una sola fila
$contenidoCelda = 'Nombre:        ' . utf8_decode($datosC['nombre']) . '                                                   
                                     Fecha:          ' . utf8_decode($fechaHoraActual);
$pdf->Cell(19, 0.5, $contenidoCelda, 1, 1, 'C');
$contenidoCelda = '          NIT:      ' . utf8_decode($datosC['telefono']) . '                                                   
                                                    Direccion:    ' . utf8_decode($datosC['direccion']);
$pdf->Cell(19, 0.5, $contenidoCelda, 1, 0.5, 'L');

$pdf->Ln(0.4);

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(19, 0.8, "Detalle de Producto", 1, 1, 'C', 1);
$pdf->Ln(0.1);
$pdf->SetTextColor(1, 1, 1);

// Encabezados de la tabla
$pdf->Cell(5, 0.5, utf8_decode('Descripción'), 1, 0, 'C');
$pdf->Cell(5, 0.5, 'Cantidad.', 1, 0, 'C');
$pdf->Cell(5, 0.5, 'Precio Unitario', 1, 0, 'C');
$pdf->Cell(4, 0.5, 'Sub Total.', 1, 1, 'C');

$pdf->SetFont('Arial', '', 10);

// Contenido de la tabla
$total = 0.00;
$desc = 0.00;


while ($row = mysqli_fetch_assoc($ventas)) {
    // Dibujar el cuadro exterior en la primera y última fila
    if ($row === reset($ventas) || $row === end($ventas)) {
        // Línea superior
        $pdf->Rect(1, $pdf->GetY(), 19, 0.5, 'S');

        // Línea izquierda
        $pdf->Rect(1, $pdf->GetY(), 0, 0.5, 'S');

        // Línea derecha
        $pdf->Rect(20, $pdf->GetY(), 0, 0.5, 'S');

        // Línea inferior
        $pdf->Rect(1, $pdf->GetY(), 19, 0.5, 'S');
    }
   
    // Dibujar las líneas verticales en las filas intermedias
    if ($row !== reset($ventas) && $row !== end($ventas)) {
        $pdf->Line(1, $pdf->GetY(), 1, $pdf->GetY() + 0.5);
        $pdf->Line(20, $pdf->GetY(), 20, $pdf->GetY() + 0.5);
    }

    // Dibujar las líneas horizontales de separación de columnas
    for ($i = 6; $i < 38; $i += 5) {
        $pdf->Line($i, $pdf->GetY(), $i, $pdf->GetY() + 0.5);
    }

    // Celda de descripción
    $pdf->SetXY(1, $pdf->GetY());
    $pdf->Cell(5, 0.5, $row['descripcion'], 0);
    $pdf->Cell(4, 2, '', 0);

    // Celda de cantidad
    $pdf->SetXY(7, $pdf->GetY());
    $pdf->Cell(4, 0.5, $row['cantidad'], 0);
    $pdf->Cell(4, 2, '', 0);
    // Celda de precio
    $pdf->SetXY(12, $pdf->GetY());
    $pdf->Cell(4, 0.5, 'Q ' . $row['precio'], 0);
    $pdf->Cell(4, 2, '', 0);
    

    // Calcular sub total y actualizar total y descuento
    $sub_total = $row['total'];
    $total = $total + $sub_total;
    $desc = $desc + $row['descuento'];
   
    $pdf->Cell(4, 2, '', 0);
    // Celda de total
    $pdf->SetXY(16, $pdf->GetY());
    $pdf->Cell(1, 0.5, 'Q ' . number_format($sub_total, 2, '.', ','), 0, 1, 'L');
    
    
    // Mover a la posición de la próxima fila sin dibujar líneas
    $pdf->SetX(1);
    
    
}


// Total en letras y Total en números

$pdf->SetFont('Arial', 'B', 9);



$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(15, 0.5, 'Total:  ', 1, 0, 'R');
$pdf->Cell(4, 0.5, 'Q ' . number_format($total, 2, '.', ','), 1, 1, 'C');

$pdf->Ln(0.2);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(15, 0.5, 'Descuento:  ', 1, 0, 'R');
$pdf->Cell(4, 0.5, 'Q ' . number_format($desc, 2, '.', ','), 1, 1, 'C');
$pdf->Ln(0.5);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(20, 0.5, 'Gracias por su compra', 0, 1, 'C');//pendiente de modificar
$pdf->Output("ventas.pdf", "I");

?>