<?php

namespace App\Livewire;

use Livewire\Component;

class Nav extends Component
{
    public function render()
    {
        $user = auth()->user();

        $frases = [
            '"El ‘no’ ya lo tienes, ahora ve por el ‘sí’."',
            '"No vendes un producto, vendes una solución."',
            '"Cada ‘no’ te acerca más a un ‘sí’."',
            '"El éxito en ventas es 80% actitud y 20% técnica."',
            '"Si no crees en lo que vendes, nadie más lo hará."',
            '"El miedo a rechazo desaparece con acción."',
            '"No esperes oportunidades, créalas."',
            '"Un vendedor exitoso ve oportunidades donde otros ven obstáculos."',
            '"Tu energía atrae tu éxito."',
            '"Si puedes soñarlo, puedes venderlo."',
            '"Detrás de cada gran venta, hay múltiples intentos."',
            '"El fracaso es solo un escalón hacia el éxito."',
            '"Si te rindes, ¿quién ganará por ti?"',
            '"Los campeones siguen vendiendo cuando otros descansan."',
            '"No cuentes los rechazos, cuenta los aprendizajes."',
            '"Cada llamada, cada reunión, es una nueva oportunidad."',
            '"La persistencia convierte el ‘no’ en ‘ahora no’."',
            '"Si fuera fácil, todos lo harían."',
            '"El éxito llega cuando superas lo que creías tu límite."',
            '"Hoy es un buen día para vender."',
            '"Confía en tu producto, pero más en ti mismo."',
            '"No vendas por necesidad, vende por convicción."',
            '"El cliente no compra precio, compra confianza."',
            '"Tu seguridad cierra más tratos que tu discurso."',
            '"Si no te emociona tu oferta, menos al cliente."',
            '"Habla menos, escucha más y vende mejor."',
            '"La persuasión no es manipulación, es conexión."',
            '"Un ‘no’ es solo una pregunta sin respuesta correcta."',
            '"El mejor vendedor es el mejor solucionador de problemas."',
            '"No convenzas, demuestra valor."',
            '"No trabajes por comisión, trabaja por legado."',
            '"Las metas son el GPS del vendedor exitoso."',
            '"Si no tienes metas, estás trabajando para las de otro."',
            '"Rompe tus récords antes que la competencia lo haga."',
            '"No sueñes con el éxito, trabaja por él."',
            '"Cada venta cuenta, cada cliente importa."',
            '"El cierre no es el final, es el comienzo de la próxima venta."',
            '"Lo que se mide, se mejora."',
            '"No busques clientes, construye relaciones."',
            '"Tu ingreso refleja tu impacto, no tu esfuerzo."',
            '"La suerte favorece al vendedor que llama más."',
            '"No digas ‘mañana’, di ‘ahora’."',
            '"El mejor momento para vender fue ayer, el segundo mejor es hoy."',
            '"Si no avanzas, la competencia lo hará."',
            '"La acción mata el miedo."',
            '"No esperes a que te den leads, búscalos."',
            '"Un ‘no’ duele menos que un ‘qué hubiera pasado’."',
            '"El teléfono no muerde, ¡llama!"',
            '"Vender es servir, no presionar."',
            '"El éxito está en la agenda: más citas, más ventas."',
            '"El mejor vendedor es un eterno aprendiz."',
            '"Cada rechazo es una lección disfrazada."',
            '"No eres el mejor, pero puedes serlo."',
            '"Estudia a los que ganan más que tú."',
            '"Invierte en tu crecimiento como en tu cartera."',
            '"Si no evolucionas, te volverás obsoleto."',
            '"Lee más, aprende más, vende más."',
            '"La competencia no duerme, ¿y tú?"',
            '"Domina tu producto, domina el mercado."',
            '"No repitas errores, repite éxitos."',
            '"La gente compra de quienes confían."',
            '"No vendas, haz amigos y ellos te comprarán."',
            '"Un cliente satisfecho trae 10 más."',
            '"Cuida más a tus clientes que a tu comisión."',
            '"El ‘gracias’ después de la venta abre la próxima."',
            '"No cierres una venta, abre una relación."',
            '"Tu red de contactos es tu red de ingresos."',
            '"Escucha más, vende mejor."',
            '"Un cliente feliz es tu mejor publicidad."',
            '"No hables mal de la competencia, habla bien de ti."',
            '"Hoy es el día para batir tu récord."',
            '"El éxito es una decisión, no un accidente."',
            '"Si no amas las ventas, el dinero no te alcanzará."',
            '"No trabajes por dinero, trabaja por pasión."',
            '"El primer ‘sí’ del día alimenta la motivación."',
            '"Cada mañana es una nueva oportunidad para vender."',
            '"Si no sales con energía, vuelve con resultados."',
            '"El entusiasmo es contagioso: ¡inféctate y contagia!"',
            '"No cuentes los días, haz que los días cuenten."',
            '"El vendedor que cree en sí mismo, convence a los demás."',
            '"Las ventas son el puente entre tus sueños y tu realidad."',
            '"No limites tus retos, reta tus límites."',
            '"El éxito no es para los que esperan, sino para los que actúan."',
            '"Si quieres más, da más."',
            '"La excelencia en ventas no es un acto, es un hábito."',
            '"No te conformes con lo mínimo, exígele a tu máximo."',
            '"El dinero sigue al valor, no al deseo."',
            '"No busques atajos, domina el proceso."',
            '"El fracaso es temporal, la renuncia es permanente."',
            '"Tus clientes no compran tu producto, compran TU energía."',
            '"Un ‘no’ es solo el comienzo de la negociación."',
            '"No cierres una venta, ayuda a alguien a tomar una decisión."',
            '"Si no pides el cierre, nunca venderás."',
            '"La objeción es la antesala de la venta."',
            '"No hay venta sin cierre, como no hay agua sin gotear."',
            '"Si no crees en el cierre, el cliente tampoco."',
            '"El mejor momento para cerrar es CUANDO EL CLIENTE QUIERE."',
            '"No dejes que el cliente piense demasiado, guíalo."',
            '"Un cierre natural es el resultado de una buena conexión."',
            '"Vender no es persuadir, es ayudar a decidir."',

        ];
        // Seleccionar una frase aleatoria
        $fraseAleatoria = $frases[array_rand($frases)];

        // Determinar el saludo según la hora del día
        $hora = now()->format('H');
        if ($hora < 12) {
            $saludo = '¡Buenos días, ' . $user->name . '! Bienvenido/a Relevant';
        } elseif ($hora < 18) {
            $saludo = '¡Buenas tardes, ' . $user->name . '! Bienvenido/a Relevant';
        } else {
            $saludo = '¡Buenas noches, ' . $user->name . '! Bienvenido/a Relevant';
        }

        return view('livewire.nav', compact(
            'user',
            'frases',
            'fraseAleatoria',
            'saludo',
        ));
    }
}
