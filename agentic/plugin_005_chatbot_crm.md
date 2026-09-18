# Plugin 005 — API Chatbot Multicanal con IA integrado a CRM

> **Slug:** `chatbot-crm` | **Prefijo DB:** `plg_chat_` | **Versión:** 1.0.0
> **Referencia:** `ARQUITECTURA_PLUGIN_SYSTEM.md`
> **Ruta base:** `/nat/plugins/chatbot/`
> **IA:** Gemini (Google) / DeepSeek — configurable por canal

---

## 1. Alcance y Objetivo

Sistema de comunicación multicanal con IA que:
- Recibe mensajes de **WhatsApp** (Meta API), **Telegram** (Bot API) y **chat web** (widget JS)
- Procesa mensajes con **Gemini** o **DeepSeek** para respuestas automáticas inteligentes
- Captura leads y los vincula al **CRM** del ERP (clientes, prospectos)
- Mantiene historial completo de conversaciones por canal y por cliente
- Permite que un agente humano tome el control de una conversación ("handoff")
- Detecta intenciones: cotización, soporte, información, escalamiento

---

## 2. Arquitectura de Canales

```
WhatsApp (Meta API)   ──┐
Telegram (Bot API)    ──┤── Webhook → ChatbotController::webhook() → ChannelRouter
Chat Web (JS Widget)  ──┘                                               │
                                                                        ▼
                                                               ConversationManager
                                                                        │
                                                     ┌──────────────────┴──────────────────┐
                                                     ▼                                     ▼
                                              AIEngine                           HumanAgent
                                    (Gemini / DeepSeek)                    (interfaz en ERP)
                                                     │
                                                     ▼
                                            CrmIntegration
                                    (crear/actualizar contacto)
```

---

## 3. Base de Datos

### `plg_chat_channels` — Canales configurados

```sql
id                INT AI PK
name              VARCHAR(100)         -- "WhatsApp Principal", "Telegram Soporte"
type              ENUM('whatsapp','telegram','webchat')
config            JSON                 -- tokens, webhook secrets, page_id, etc.
ai_model          ENUM('gemini','deepseek','none') DEFAULT 'gemini'
ai_system_prompt  TEXT NULL            -- prompt del sistema para este canal
ai_enabled        TINYINT(1) DEFAULT 1
status            ENUM('active','inactive','error') DEFAULT 'active'
branch_id         INT NULL FK org_branches.id
created_by        INT FK users.id
created_at, updated_at
```

### `plg_chat_contacts` — Contactos de canales (leads)

```sql
id                INT AI PK
channel_id        INT FK plg_chat_channels.id
external_id       VARCHAR(255)         -- phone en WA, chat_id en TG, session en webchat
name              VARCHAR(150) NULL
phone             VARCHAR(20) NULL
email             VARCHAR(150) NULL
crm_client_id     INT NULL             -- FK futura a crm_clients.id
status            ENUM('active','blocked','converted') DEFAULT 'active'
first_contact_at  DATETIME
last_contact_at   DATETIME NULL
notes             TEXT NULL
created_at, updated_at
```

### `plg_chat_conversations` — Conversaciones

```sql
id                INT AI PK
channel_id        INT FK plg_chat_channels.id
contact_id        INT FK plg_chat_contacts.id
status            ENUM('open','pending','resolved','assigned')
assigned_to       INT NULL FK users.id  -- agente humano asignado
intent            VARCHAR(100) NULL     -- 'quote_request','support','info','escalation'
ai_context        JSON NULL             -- contexto acumulado para el LLM
started_at        DATETIME
resolved_at       DATETIME NULL
created_at, updated_at
```

### `plg_chat_messages` — Mensajes individuales

```sql
id                INT AI PK
conversation_id   INT FK plg_chat_conversations.id
direction         ENUM('inbound','outbound')
sender_type       ENUM('contact','ai','agent')
agent_id          INT NULL FK users.id
content           TEXT
message_type      ENUM('text','image','document','audio','sticker') DEFAULT 'text'
media_url         VARCHAR(500) NULL
external_msg_id   VARCHAR(255) NULL     -- ID del mensaje en la plataforma
status            ENUM('sent','delivered','read','failed') DEFAULT 'sent'
ai_model_used     VARCHAR(50) NULL
tokens_used       INT NULL
sent_at           DATETIME
```

### `plg_chat_quick_replies` — Respuestas rápidas configurables

```sql
id                INT AI PK
channel_id        INT NULL FK plg_chat_channels.id  -- NULL = global
title             VARCHAR(100)
content           TEXT
tags              VARCHAR(255) NULL
active            TINYINT(1) DEFAULT 1
created_at, updated_at, deleted_at
```

---

## 4. Servicios de IA

### `GeminiClient.php`

```php
namespace Plugins\ChatbotCrm\Services;

class GeminiClient
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param array $messages [['role'=>'user','content'=>'...'],['role'=>'model','content'=>'...']]
     */
    public function chat(array $messages, string $systemPrompt = ''): string
    {
        $contents = [];
        if ($systemPrompt) {
            $contents[] = ['role' => 'user', 'parts' => [['text' => $systemPrompt]]];
            $contents[] = ['role' => 'model', 'parts' => [['text' => 'Entendido.']]];
        }
        foreach ($messages as $msg) {
            $contents[] = [
                'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $client = \Config\Services::curlrequest();
        $response = $client->post(self::API_URL . '?key=' . $this->apiKey, [
            'headers' => ['Content-Type' => 'application/json'],
            'json'    => ['contents' => $contents],
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}
```

### `DeepSeekClient.php` (OpenAI-compatible API)

```php
class DeepSeekClient
{
    private const API_URL = 'https://api.deepseek.com/v1/chat/completions';
    private string $apiKey;

    public function chat(array $messages, string $systemPrompt = ''): string
    {
        $allMessages = $systemPrompt
            ? array_merge([['role' => 'system', 'content' => $systemPrompt]], $messages)
            : $messages;

        $client = \Config\Services::curlrequest();
        $response = $client->post(self::API_URL, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'json' => [
                'model'    => 'deepseek-chat',
                'messages' => $allMessages,
                'stream'   => false,
            ],
        ]);
        $body = json_decode($response->getBody(), true);
        return $body['choices'][0]['message']['content'] ?? '';
    }
}
```

---

## 5. Controladores y Rutas

### Rutas públicas (webhooks — fuera del grupo `session`)

```php
// Webhooks de plataformas externas (no requieren autenticación Shield)
$routes->get('/webhook/chatbot/(:segment)/verify', 'WebhookController::verify/$1');
$routes->post('/webhook/chatbot/(:segment)', 'WebhookController::receive/$1');
// Chat web widget
$routes->post('/webchat/send', 'WebchatController::send', ['as' => 'webchat.send']);
$routes->get('/webchat/widget.js', 'WebchatController::widget');
```

### Rutas internas (requieren autenticación)

| Método | Alias | Permiso |
|--------|-------|---------|
| `index()` | `chatbot.index` | `chat.access` |
| `channels()` | `chatbot.channels` | `chat.manage` |
| `conversations_ajax()` | `chatbot.conversations` | `chat.access` |
| `conversation_show(id)` | `chatbot.conv.show` | `chat.access` |
| `messages_ajax(convId)` | `chatbot.messages` | `chat.access` |
| `send_message()` | `chatbot.send` | `chat.access` |
| `assign_agent()` | `chatbot.assign` | `chat.manage` |
| `resolve()` | `chatbot.resolve` | `chat.access` |
| `contacts_ajax()` | `chatbot.contacts` | `chat.access` |
| `quick_replies()` | `chatbot.quick_replies` | `chat.manage` |
| `settings()` | `chatbot.settings` | `chat.manage` |

---

## 6. Vistas

### `chatbot_inbox.php` — Bandeja de conversaciones
- 2 columnas: lista de conversaciones (filtradas por canal/estado/agente) + panel de mensajes
- Conversaciones en tiempo real: polling cada 5s o SSE (Server-Sent Events)
- Indicadores: nuevo, pendiente IA, asignado a mí, todos
- Input de respuesta con respuestas rápidas (autocomplete)
- Botón "Tomar control" para handoff de IA a humano
- Indicador de canal (ícono WhatsApp/Telegram/Web)

### `chatbot_channels.php` — Configuración de canales
- DataTable de canales con modal de configuración
- Test de conexión por canal (verificar tokens)
- Editor del prompt del sistema IA

---

## 7. Widget de Chat Web

El plugin expone un snippet JS que el cliente embebe en su sitio:

```html
<!-- En el sitio del cliente -->
<script src="https://erp.empresa.com/webchat/widget.js?channel=abc123"></script>
```

El widget:
- Burbuja flotante (color personalizable)
- Ventana de chat iframe o inline
- Identifica al visitante por `localStorage` UUID
- Envía mensajes via `POST /webchat/send`

---

## 8. Flujo de Handoff IA → Humano

1. IA recibe mensaje que no puede resolver (umbral de confianza bajo)
2. IA responde: "Voy a conectarte con un agente. Un momento..."
3. Conversación cambia a `status = 'pending'`
4. Alerta automática: `_detectPendingHandoffs()` → notifica a agentes disponibles
5. Agente hace clic en "Tomar control" → `conversation.assigned_to = user.id`, `status = 'assigned'`
6. Agente responde directamente (mensajes con `sender_type = 'agent'`)
7. Agente marca como resuelta → `status = 'resolved'`

---

## 9. Integración CRM

Cuando un contacto del chatbot se identifica (email/teléfono):
1. Búsqueda en `crm_clients` (si plugin CRM está activo)
2. Si se encuentra: vincular `plg_chat_contacts.crm_client_id`
3. Si no: crear nuevo registro en CRM

Hook: `chatbot.contact.identified` → CRM plugin escucha y auto-crea el contacto.

---

## 10. Permisos

```json
"permissions": {
  "chat.access":    "Ver y responder conversaciones del chatbot",
  "chat.manage":    "Configurar canales, prompts y respuestas rápidas",
  "chat.analytics": "Ver estadísticas y reportes del chatbot"
}
```

---

## 11. Alertas Automáticas

| Detector | Condición |
|----------|-----------|
| `_detectPendingHandoffs` | Conversaciones `pending` (esperando agente) > 15 min |
| `_detectOpenConversations` | Conversaciones `open` sin respuesta > 1 hora |

---

## 12. Cumplimiento §1–§21

| § | Aplicación en este plugin |
|---|--------------------------|
| §1 | `assertAjaxPost()` en todos los POST internos. Los webhooks externos (`/webhook/chatbot/*`) son POST SIN guard AJAX (son llamadas de Meta/Telegram, no del browser) |
| §2 | CSRF en vistas internas. Los webhooks y webchat público no usan CSRF (peticiones externas) |
| §3 | Alias `chatbot.*`. Solo `route_to()` en vistas internas |
| §4 | `url_is('nat/plugins/chatbot*')` en sidebar |
| §5/§16 | `DataTableTrait` en `ConversationModel` y `ContactModel` para las vistas de administración |
| §6 | `isDataTable` guard en vistas con DataTable |
| §7 | Rutas internas con `permission:chat.*`. Webhooks sin auth Shield (verificación por HMAC del proveedor) |
| §8 | `logActivity` en channel_create, channel_update, channel_delete, agent_takeover, conversation_resolved |
| §9 | `ConversationModel::createWithContact()` con transacción cuando se crea contacto + conversación |
| §10 | Select2 con `dropdownParent` en modales |
| §11 | Fechas de mensajes mostradas en `d/m/Y H:i`. ISO en DB |
| §12 | FontAwesome: `fa-comment-dots`, `fa-whatsapp` (brand), `fa-telegram` (brand), `fa-robot` |
| §13 | Breadcrumbs en vistas de administración |
| §14 | Vistas extienden `$layout`. Widget webchat usa su propio HTML mínimo |
| §15 | `jsonWithCsrf()` en POST internos. Webhooks devuelven JSON plano (sin CSRF) |
| §17 | `notifyShow()` para feedback en admin. Sin alerts en webhooks |
| §18 | Medios recibidos (imágenes, audios) en `WRITEPATH/uploads/plugins/chatbot-crm/media/` |
| §19 | `useSoftDeletes = true` en `ConversationModel`, `ContactModel`. `$allowedFields` explícito |
| §20 | Tipos de mensajes e intenciones como constantes en el modelo |
| §21 | Detectores de alertas con formato estándar. `_safeDetect()` en `_detectAll()` |

---

## Prompt para el agente implementador

```
Actúa como un desarrollador experto en CodeIgniter 4. Lee y ejecuta exactamente el plan:
@agentic/plugin_005_chatbot_crm.md

Lee también:
- @DOCUMENTACION_TECNICA.md (§1–§21 obligatorios)
- @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md
- @agentic/plugin_000_plugin_system_core.md (prerequisito ya instalado)

Prefix de tablas: plg_chat_
Namespace: Plugins\ChatbotCrm\

Importante:
- Los webhooks /webhook/chatbot/* deben estar FUERA del grupo session/CSRF de Shield.
  La seguridad de los webhooks se hace verificando la firma HMAC del proveedor (X-Hub-Signature para Meta, token para Telegram).
- Las credenciales API (tokens de canal) se cifran en la columna config (JSON) de plg_chat_channels.
- El widget de webchat es JavaScript puro que carga desde /webchat/widget.js — sin autenticación.
- En v1 implementar solo WhatsApp Cloud API y Telegram. El chat web es opcional para v1.
- GeminiClient y DeepSeekClient van en Services/. En v1 implementar solo Gemini.

Al terminar, añade tu reporte al final de @agentic/AUDITORIA_ARQUITECTURA.md.
```
