# Plan: conectar una máquina de efectivo Cashlogy al POS

Estado: **idea con cliente interesado, sin empezar.** Nada de esto está construido ni probado.

> Nota: el manual de integración lleva la marca «Confidential document», pero el usuario indica que se obtuvo de internet y decide incluir aquí su protocolo. El repositorio es público.

## Objetivo

Que el cajero pulse «Cobrar en efectivo» en el POS, que el POS mande el importe a la máquina Cashlogy y que, al terminar el cobro (billetes, monedas y cambio), el POS cierre el pedido con lo que de verdad se cobró. El cajero no toca dinero ni teclea importes.

## Documentación que tenemos (copia en `_dev/docs/cashlogy/`)

| Archivo | Qué es |
|---|---|
| `cashlogy-connector-manual-integracion.pdf` | **Manual de integración de Cashlogy Connector** (53 págs.). Venía como `M-23G-504-04.pdf`: el nombre original engaña, no es de un cajón. |
| `azkoyen-rbh500-manual-tecnico.pdf` | Manual técnico del módulo de billetes Azkoyen RBH500 (Cashlogy L+), 76 págs. Venía como `84f48d.pdf`, de manualslib.es. |

## Lo que dicen los manuales (confirmado leyéndolos)

- La máquina es un dispositivo «esclavo»: se conecta **por cable (USB o RS232) al ordenador de la caja**. No hay conexión por WiFi ni nube en esos manuales.
- El software **Cashlogy Connector** es un programa de **Windows** que se instala en ese ordenador y se descarga de sat.azkoyen.com o sat.cashlogy.com.
- El Connector funciona como **servidor en red local (TCP/IP)**, con un puerto configurable. El POS le manda líneas de texto y espera la respuesta.
- Operaciones disponibles: arranque (comprueba conexión), cobrar un importe, devolver efectivo, estado de la máquina, total de efectivo, pantalla de gestión, cierre de caja y errores.
- En el cobro, el Connector muestra sus propias pantallas, cuenta lo que entra, da el cambio y responde con lo cobrado, lo devuelto y si se canceló. El manual pide que el POS compruebe que cobrado menos devuelto coincide con el importe.
- El manual habla de un **simulador** de Cashlogy para pruebas.

## Protocolo del Connector (resumen del manual de integración)

El manual es de 2012-2013 (revisiones hasta julio de 2013) y habla de la versión 1.03 del protocolo. **Hay que comprobar con Cashlogy si sigue vigente** en el Connector 2.0 que lleva la máquina actual.

**Cómo se habla.** El Connector escucha en un puerto TCP configurable (recomendado **8092**). Se manda una cadena de texto con campos separados por `#` y se espera la respuesta. Cada comando termina con `#`. Los importes van **en céntimos** (1220 = 12,20 €). El manual que he leído **no explica cómo se delimita un mensaje en el socket** (por ejemplo, un salto de línea): hay que comprobarlo con el simulador o en la fase 0.

**Orden de uso.** Se lanza `CashlogyConnector.exe` antes o a la vez que el POS. El primer comando es `#I#` y solo se manda una vez al arrancar. `#E#` cierra el Connector al cerrar el POS. Solo se puede ejecutar un comando a la vez: si llega otro mientras hay uno pendiente, responde `#ER:BUSY#`. `#Z#` reinicia y se puede mandar en cualquier momento.

**Respuesta.** Empieza por `#0#` si todo fue bien. Códigos: `#ER:GENERIC#` fallo de la máquina, `#WR:LEVEL#` aviso de nivel de monedas o billetes (no es un fallo), `#ER:BUSY#`, `#ER:BAD_DATA#` parámetros mal enviados, `#WR:CANCEL#` el usuario canceló, `#ER:CLAIM#` otro POS usa la máquina.

| Comando | Para qué | Respuesta |
|---|---|---|
| `#I#` | Arranque y comprobación de conexión | `#0#versión#` |
| `#C#…#` | **Cobrar** (ver abajo) | `#0#cobrado#devuelto#manual#cambio añadido#` |
| `#P#importe#…#` | **Devolver** efectivo | `#0#importe devuelto#…#` |
| `#D#` | Estado: total de dinero | `#0#total#` |
| `#T#` | Total en reciclador y en stacker | `#0#reciclador#stacker#` |
| `#X#valor#` | Cuántas monedas o billetes de un valor hay | `#0#reciclador#stacker#` |
| `#G#…#` | Pantalla de gestión (backoffice) | `#0#total antes#total después#entrado#salido#` |
| `#A#` `#1#` `#R#` `#S#` `#H#` `#V#` `#F#` | Añadir cambio, monedas de 1 cént., retirar efectivo, retirar del stacker, dar cambio, vaciar, cierre de caja | cada uno devuelve importes |
| `#L#` `#M#` `#N#` `#O#` `#K#` | Ver el log, estadísticas, mantenimiento, poner a cero | `#0#` o error |
| `#B#` `#Q#` `#J#` | Modo avanzado: activar admisión, preguntar lo cobrado hasta ahora, desactivar (ver abajo) | `#0#`, `#0#importe#`, `#0#importe#` |
| `#?#` | Preguntar si hay un error | `#0#` o error |

**Cobro estándar `#C#`.** Parámetros: número de operación, código de caja, importe en céntimos, segunda pantalla (sí/no y su posición X, Y), mostrar botón «Aceptar» (cobro con confirmación) o cobro automático al llegar al importe, aceptar cobro parcial, pantalla encima de las demás, caja para céntimos a mano y botón de cobro manual. El Connector enseña sus pantallas, cuenta lo que entra, da el cambio y responde. **El POS debe comprobar que cobrado + manual − devuelto coincide con el importe.** Si no coincide, hubo un error o el usuario canceló. No acepta importes negativos.

**Modo avanzado (`#B#`, `#Q#`, `#J#`, `#P#`).** Se activa la admisión antes de registrar los productos; el cliente puede ir metiendo dinero mientras se escanea. El POS pregunta con `#Q#` cuánto ha entrado, y al terminar manda `#J#`, calcula el cambio y lo devuelve con `#P#`. Entre `#B#` y `#J#` solo se aceptan `#Q#`, `#J#`, `#X#` y `#Z#`. El manual describe también un **cobro sin las pantallas del Connector**: el POS consulta `#Q#` cada poco y dibuja él mismo la pantalla. Es el más flexible para integrarlo en la interfaz del POS.

**Cobro manual.** Permite registrar un billete o moneda sin meterlo en la máquina (por ejemplo, un billete dañado). El POS debe tener en cuenta lo cobrado automáticamente y lo cobrado a mano.

**Simulador.** El manual dice que al trabajar con el simulador de Cashlogy hay que poner «pantalla encima» a 0 para poder hacer pruebas, y con la máquina real a 1.

**Para las pruebas de la fase 0, sin dinero:** `#I#`, `#D#`, `#T#`, `#X#` y un `#C#` que se cancela.

## Decisión de diseño pendiente: dos formas de cobrar

1. **Estándar (`#C#`).** El POS manda el importe y deja que el Connector muestre sus pantallas. Es lo más rápido de hacer y el manual lo llama integración «Express». Estas pantallas salen en el ordenador de la caja, encima del navegador.
2. **Avanzado sin pantallas (`#B#`, `#Q#`, `#J#`, `#P#`).** El POS dibuja su propia pantalla de cobro y consulta la máquina. Es más trabajo, pero el aspecto es el del POS, y encaja mejor con el diseño del bridge.

Recomendación para empezar: la estándar. Cuando funcione, valorar la avanzada.

## El problema técnico principal

El POS de Webkul corre en el **navegador**, y un navegador no puede abrir una conexión TCP directa con el Connector. Hace falta un **puente local**: un programa pequeño en el mismo Windows de la caja que reciba la petición del navegador y se la pase al Connector.

**Sin comprobar:** si el navegador deja que una página `https` hable con ese puente local. Se prueba en la fase 0 y puede cambiar el plan.

## Cómo se probará

Se hace con una **tienda de pruebas nuestra**, no con el POS de la clienta. La clienta solo aporta su ordenador de caja con la máquina, por AnyDesk. En su ordenador solo se instala el puente. Alguien en la tienda mete el dinero cuando haga falta.

## Fases

| Fase | Qué se hace | Dinero |
|---|---|---|
| 0. Conexión | Prueba corta por AnyDesk: el Connector responde al arranque, al estado y a un cobro que se cancela sin meter nada. Decide si es viable desde el navegador. | No |
| 1. Cobro | Botón de cobro en el POS: importe, cobrado, cambio y cancelar. | Sí, importes pequeños, con alguien en tienda |
| 2. Casos límite | Corte a mitad de cobro, cobro parcial, devolución de efectivo, cuadre con el cajón de Webkul. | Sí |
| 3. Producto | Ajustes en el administrador, documentación, instalación para otros clientes y release. | No |

La fase 0 decide todo lo demás. Si falla, se replantea antes de seguir.

## Antes de la fase 0

1. Comprobar la versión real de la tienda de pruebas del servidor `pos.vfwoo.online` (**sin verificar**). Si hay que actualizarla: `validar-deploy` y OK explícito.
2. Confirmar la licencia de Webkul en ese servidor (tema abierto de la estrategia de licencias para demos).
3. Pedir a Cashlogy el **simulador** y confirmar que existe.
4. Pedir a la clienta: modelo exacto de la máquina, confirmar que el ordenador es Windows y que tiene el Connector instalado, acceso por AnyDesk y horario con alguien en tienda.

## Riesgos

- Que el navegador bloquee la llamada al puente local.
- Cortes de luz o de internet a mitad de un cobro: el pedido y el dinero deben quedar coherentes. Es la parte delicada.
- Cuadre entre el cajón de efectivo de Webkul y lo que tiene la máquina.
- Solo vale en el ordenador Windows donde esté la máquina, no en una tablet.
- Mantenimiento del puente en cada tienda.

## Decisiones pendientes

- Dónde vive el puente y cómo se instala en la tienda.
- Si se hace como parte del bridge o como un programa aparte.
- Precio y plazo: **no se puede dar** hasta terminar la fase 0.

## Pendiente de comprobar con Cashlogy o en la fase 0

- Si el protocolo 1.03 del manual sigue vigente en la máquina actual.
- Cómo se delimita un mensaje en el socket (el manual leído no lo dice).
- Si el simulador existe y dónde se descarga (sat.azkoyen.com o sat.cashlogy.com).
- Qué pasa con un cobro pendiente si el POS o el puente se cuelgan (`#Z#` reinicia).
- Qué hace la máquina si la cancelación llega con dinero ya dentro: el manual dice que devuelve lo introducido y responde `#WR:CANCEL#`.

## Respuesta dada a la clienta

Es posible, hay documentación oficial. Para probar hace falta su ordenador por AnyDesk y alguien en tienda. Sin precio ni plazo por ahora.
