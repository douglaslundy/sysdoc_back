<?php

/*
|--------------------------------------------------------------------------
| Catálogo de mensagens do sistema
|--------------------------------------------------------------------------
| Os textos NÃO ficam mais no código: vivem na tabela protocol_alerts (tela Alertas), onde o administrador
| edita. Este arquivo é só a SEMENTE: a migration cria as linhas a partir daqui, o botão "Restaurar padrão"
| volta a elas e, se a linha do banco não existir (ex.: código atualizado antes da migration), o envio
| usa estes valores para nunca deixar de avisar.
|
|  sistema  "modulo:gatilho" => mensagens enviadas por código próprio (destinatários e canais são fixos no código;
|            no banco só o texto, o assunto e o ativar/desativar são editáveis). `variaveis` lista as {{variavel}} aceitas.
|  padroes  gatilho => texto padrão dos alertas por módulo criados na tela (usado quando o campo Template fica vazio).
*/
return [
    'sistema' => [
        'os:os_aberta_gestor' => [
            'nome' => 'OS aberta: aviso aos gestores',
            'descricao' => 'Enviado aos gestores ativos da secretaria de destino quando uma ordem de serviço é aberta.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'Nova OS {{os_numero}}',
            'template' => 'Nova ordem de serviço {{os_numero}} para a secretaria: {{os_local}}.',
            'variaveis' => ['os_numero', 'os_local', 'os_tecnico', 'os_prazo'],
        ],
        'os:os_aberta_solicitante' => [
            'nome' => 'OS aberta: confirmação ao solicitante',
            'descricao' => 'Enviado a quem abriu a ordem de serviço.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'OS {{os_numero}} registrada',
            'template' => 'Sua ordem de serviço {{os_numero}} foi registrada.',
            'variaveis' => ['os_numero', 'os_local', 'os_tecnico', 'os_prazo'],
        ],
        'os:os_tecnico' => [
            'nome' => 'OS atribuída: aviso ao técnico',
            'descricao' => 'Enviado ao técnico quando uma ordem de serviço é atribuída a ele.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'OS {{os_numero}} designada a você',
            'template' => 'Você foi designado para a OS {{os_numero}} ({{os_local}}). Acesse o sistema para iniciar o atendimento.',
            'variaveis' => ['os_numero', 'os_local', 'os_tecnico', 'os_prazo'],
        ],
        'os:os_tecnico_solicitante' => [
            'nome' => 'OS atribuída: aviso ao solicitante',
            'descricao' => 'Enviado a quem abriu a OS quando um técnico é atribuído.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'OS {{os_numero}} atribuída',
            'template' => 'A sua OS {{os_numero}} ({{os_local}}) foi atribuída ao técnico {{os_tecnico}}.',
            'variaveis' => ['os_numero', 'os_local', 'os_tecnico', 'os_prazo'],
        ],
        'os:os_status' => [
            'nome' => 'OS: mudança de situação',
            'descricao' => 'Enviado a gestores, técnico e solicitante quando a situação da OS muda (iniciada, encaminhada, cancelada...). Na conclusão o solicitante recebe a mensagem própria.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'OS {{os_numero}} {{os_status}}',
            'template' => 'A OS {{os_numero}} ({{os_local}}) está {{os_status}}.',
            'variaveis' => ['os_numero', 'os_local', 'os_status', 'os_tecnico', 'os_prazo'],
        ],
        'os:os_concluida_solicitante' => [
            'nome' => 'OS concluída: aviso ao solicitante',
            'descricao' => 'Enviado a quem abriu a OS quando ela é concluída.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'Sua OS {{os_numero}} foi concluída',
            'template' => 'Sua ordem de serviço {{os_numero}} ({{os_local}}) foi concluída.',
            'variaveis' => ['os_numero', 'os_local', 'os_tecnico', 'os_prazo'],
        ],
        'os:os_prazo_vencido' => [
            'nome' => 'OS com prazo vencido',
            'descricao' => 'Enviado aos gestores e ao técnico quando o prazo da OS vence.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'OS {{os_numero}} com prazo vencido',
            'template' => 'A OS {{os_numero}} ({{os_local}}) passou do prazo ({{os_prazo}}).',
            'variaveis' => ['os_numero', 'os_local', 'os_tecnico', 'os_prazo'],
        ],
        'cadastros:cadastro_recebido' => [
            'nome' => 'Cadastro público: confirmação de recebimento',
            'descricao' => 'Enviado a quem pediu cadastro pela página pública. O texto é igual para qualquer CPF (não revele se a conta existe).',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'Recebemos sua solicitação de cadastro',
            'template' => 'Recebemos sua solicitação de cadastro. Aguarde a autorização de um gestor.',
            'variaveis' => [],
        ],
        'cadastros:cadastro_novo_pedido' => [
            'nome' => 'Cadastro público: novo pedido (administradores e gestores)',
            'descricao' => 'Enviado a administradores e gestores da secretaria quando chega um pedido de cadastro. Nunca inclua CPF.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'Novo pedido de cadastro',
            'template' => "Novo pedido de cadastro no SysDoc{{protocolo_sufixo}}.\nSolicitante: {{nome}}\nCargo: {{cargo}}\nSecretaria: {{secretaria}}\nAnalise o pedido no protocolo acima.",
            'variaveis' => ['nome', 'cargo', 'secretaria', 'protocolo', 'protocolo_sufixo'],
        ],
        'cadastros:cadastro_aprovado' => [
            'nome' => 'Cadastro público: aprovado',
            'descricao' => 'Enviado ao solicitante quando o cadastro é aprovado.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'Seu cadastro foi aprovado',
            'template' => "Olá!\nSeu cadastro no SysDoc foi aprovado. Entre com seu CPF ou e-mail e a senha que você definiu no pedido.",
            'variaveis' => [],
        ],
        'cadastros:cadastro_recusado' => [
            'nome' => 'Cadastro público: recusado',
            'descricao' => 'Enviado ao solicitante quando o cadastro é recusado.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'Seu pedido de cadastro não foi aprovado',
            'template' => "Olá!\nSeu pedido de cadastro no SysDoc não foi aprovado.\nMotivo: {{motivo}}",
            'variaveis' => ['motivo'],
        ],
        'cadastros:cadastro_conta_existente' => [
            'nome' => 'Cadastro público: tentativa com conta existente',
            'descricao' => 'Enviado aos administradores (no máximo uma vez por hora por conta) quando um pedido público usa CPF ou e-mail de uma conta existente. Só vale se o alerta estiver ligado na configuração.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'Tentativa de cadastro com conta existente',
            'template' => 'Um pedido público de cadastro usou CPF ou e-mail de uma conta existente (usuário #{{usuario_id}}). Nenhum pedido foi criado.',
            'variaveis' => ['usuario_id'],
        ],
        'sistema:perfil_gestor_atribuido' => [
            'nome' => 'Perfil Gestor atribuído',
            'descricao' => 'Enviado aos administradores quando um gestor atribui o perfil Gestor a outro usuário.',
            'canais' => ['whatsapp', 'email'],
            'assunto' => 'Perfil Gestor atribuído',
            'template' => 'O gestor {{ator}} atribuiu o perfil Gestor a {{alvo}} ({{alvo_email}}).',
            'variaveis' => ['ator', 'alvo', 'alvo_email'],
        ],
        'protocolo:protocolo_whatsapp' => [
            'nome' => 'Protocolo atualizado: aviso por WhatsApp',
            'descricao' => 'Enviado ao responsável atual e ao criador do protocolo quando ele é criado, encaminhado, comentado, encerrado etc.',
            'canais' => ['whatsapp'],
            'assunto' => null,
            'template' => 'Protocolo {{protocolo_numero}} ({{protocolo_assunto}}): {{protocolo_acao}}{{protocolo_por}}.',
            'variaveis' => ['protocolo_numero', 'protocolo_assunto', 'protocolo_acao', 'protocolo_por', 'protocolo_autor'],
        ],
        'vigilancia:vigilancia_aviso_peticao' => [
            'nome' => 'Vigilância: nova petição recebida',
            'descricao' => 'Enviado por WhatsApp aos profissionais da Vigilância cadastrados quando chega uma petição. Nunca inclua dados do denunciante.',
            'canais' => ['whatsapp'],
            'assunto' => null,
            'template' => "🔔 Nova petição recebida\nProtocolo: {{protocolo}}\nMotivo: {{motivo}}\nAssunto: {{assunto}}\nLocal: {{local}}\nAcesse: {{link}}",
            'variaveis' => ['protocolo', 'motivo', 'assunto', 'local', 'link'],
        ],
        'vigilancia:vigilancia_aviso_fiscalizacao' => [
            'nome' => 'Vigilância: nova fiscalização registrada',
            'descricao' => 'Enviado por WhatsApp aos profissionais da Vigilância cadastrados quando uma fiscalização interna é criada.',
            'canais' => ['whatsapp'],
            'assunto' => null,
            'template' => "📋 Nova fiscalização registrada\nProtocolo: {{protocolo}}\nEstabelecimento: {{estabelecimento}}\nSituação: {{situacao}}\nAcesse: {{link}}",
            'variaveis' => ['protocolo', 'estabelecimento', 'situacao', 'link'],
        ],
    ],

    'padroes_gerais' => 'Alerta {{gatilho}} disparado para o modulo {{modulo}}.',

    'padroes' => [
        'solicitacao_exclusao_pendente' => 'Existe uma solicitacao pendente de exclusao para o documento {{documento_titulo}}. Acesse {{link_aprovacoes_documentos}} para analisar.',
        'solicitacao_exclusao_aprovada' => 'A solicitacao de exclusao do documento {{documento_titulo}} foi aprovada.',
        'solicitacao_exclusao_rejeitada' => 'A solicitacao de exclusao do documento {{documento_titulo}} foi rejeitada.',
        'documento_cadastrado' => 'O documento {{documento_titulo}} foi cadastrado.',
        'documento_atualizado' => 'O documento {{documento_titulo}} foi atualizado.',
        'documento_publicado' => 'O documento {{documento_titulo}} foi publicado.',
        'requisicao_criada' => 'A requisicao {{requisicao_numero}} foi criada para {{requisicao_secretaria}}.',
        'requisicao_em_analise' => 'A requisicao {{requisicao_numero}} entrou em analise.',
        'requisicao_aprovada' => 'A requisicao {{requisicao_numero}} foi aprovada.',
        'requisicao_recusada' => 'A requisicao {{requisicao_numero}} foi recusada.',
        'requisicao_em_separacao' => 'A requisicao {{requisicao_numero}} entrou em separacao.',
        'requisicao_em_processo_de_entrega' => 'A requisicao {{requisicao_numero}} esta em processo de entrega.',
        'requisicao_entregue' => 'A requisicao {{requisicao_numero}} foi entregue.',
        'requisicao_cancelada' => 'A requisicao {{requisicao_numero}} foi cancelada.',
        'kanban_item_criado' => 'O item do kanban {{kanban_titulo}} foi criado.',
        'kanban_item_atualizado' => 'O item do kanban {{kanban_titulo}} foi atualizado.',
        'kanban_status_alterado' => 'O item do kanban {{kanban_titulo}} mudou para {{kanban_status}}.',
        'oficio_cadastrado' => 'O oficio {{oficio_numero}} foi cadastrado.',
        'oficio_atualizado' => 'O oficio {{oficio_numero}} foi atualizado.',
        'oficio_protocolo_criado' => 'Foi criado um protocolo a partir do oficio {{oficio_numero}}.',
        'protocolo_criado' => 'Novo protocolo {{protocolo_numero}} ({{protocolo_assunto}}) criado por {{protocolo_autor}}.',
        'protocolo_status_alterado' => 'O protocolo {{protocolo_numero}} ({{protocolo_assunto}}) mudou para o status {{protocolo_status}}.',
        'chat_conversa_iniciada' => 'Uma nova conversa de chat foi iniciada.',
        'chat_mensagem_enviada' => 'Uma nova mensagem de chat foi enviada.',
    ],
];
