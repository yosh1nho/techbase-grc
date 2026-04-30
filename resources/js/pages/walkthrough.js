document.addEventListener('DOMContentLoaded', () => {
    const helpBtn = document.getElementById('btnGlobalHelp');
    if (!helpBtn) return;

    // Inicializar o Driver.js
    const driver = window.driver.js.driver;

    // ── Helpers para o walkthrough de ativos ──────────────────────────
    function openFirstAssetModal() {
        const firstBtn = document.querySelector('[data-open-asset]');
        if (firstBtn) firstBtn.click();
    }

    function closeAssetModal() {
        const overlay = document.getElementById('assetModal');
        if (overlay && overlay.classList.contains('open')) {
            overlay.classList.remove('open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    }

    function activateModalTab(tabId) {
        document.querySelectorAll('.am-tab').forEach(t =>
            t.classList.toggle('active', t.dataset.tab === tabId)
        );
        document.querySelectorAll('.am-tab-panel').forEach(p =>
            p.classList.toggle('active', p.id === 'tab-' + tabId)
        );
    }
    // ─────────────────────────────────────────────────────────────────

    helpBtn.addEventListener('click', () => {
        const path = window.location.pathname;
        let steps = [];

        // 1. WALKTHROUGH DO DASHBOARD
        if (path === '/' || path.includes('dashboard')) {
            steps = [
                {
                    element: '.sidebar',
                    popover: {
                        title: 'Menu de Navegação',
                        description: 'Aqui tens acesso a todos os módulos do GRC. Começa pelos Ativos e desce até à Auditoria.',
                        side: 'right', align: 'start'
                    }
                },
                {
                    element: '[data-open-alerts]',
                    popover: {
                        title: 'Alertas SIEM (Wazuh)',
                        description: 'Este painel mostra os ataques e eventos em tempo real. Clica aqui para ver os detalhes e pedir à IA para gerar um plano de resposta.',
                        side: 'bottom', align: 'start'
                    }
                },
                {
                    element: '#riskBar',
                    popover: {
                        title: 'Visão Global de Riscos',
                        description: 'Uma fotografia rápida de quantos riscos críticos e altos estão abertos neste momento.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#nextActionsContainer',
                    popover: {
                        title: 'O que fazer agora?',
                        description: 'O nosso motor cruza dados de conformidade, riscos e alertas para te dizer exatamente qual é a tua prioridade número 1 hoje.',
                        side: 'top', align: 'start'
                    }
                }
            ];
        }

        // 2. WALKTHROUGH DOS ATIVOS
        else if (path.includes('ativos') || path.includes('assets')) {
            // driverObj declarado antes dos steps para os callbacks onNextClick/onPrevClick
            // conseguirem referenciar a variável via closure
            let driverObj;

            steps = [
                // ── Página de lista ──────────────────────────────────────
                {
                    element: '#btnSyncWazuh',
                    popover: {
                        title: 'Sincronização Automática',
                        description: 'Importa e atualiza automaticamente todos os endpoints e servidores a partir do Wazuh. Garante que o inventário está sempre atual.',
                        side: 'bottom'
                    }
                },
                {
                    element: '.kpi-strip',
                    popover: {
                        title: 'Indicadores de Inventário',
                        description: 'Visão rápida do total de ativos, quantos estão monitorizados pelo Wazuh, quantos têm risco elevado e quantos agentes estão offline.',
                        side: 'bottom'
                    }
                },
                {
                    element: '.table-toolbar',
                    popover: {
                        title: 'Pesquisa & Filtros',
                        description: 'Filtra por criticidade, tipo, tag ou origem. Utiliza a barra de pesquisa para encontrar rapidamente um ativo por nome, IP ou responsável.',
                        side: 'bottom'
                    }
                },
                {
                    element: '.assets-table',
                    popover: {
                        title: 'Inventário Centralizado',
                        description: 'Cada linha é um ativo. Clica em <b>"Ver detalhes"</b> para abrir o painel completo com informação de rede, OS, risco intrínseco e análise IA.',
                        side: 'top',
                        onNextClick: () => {
                            // Abre o modal do primeiro ativo antes de avançar
                            openFirstAssetModal();
                            setTimeout(() => driverObj.moveNext(), 450);
                        }
                    }
                },

                // ── Modal: Header ────────────────────────────────────────
                {
                    element: '#assetModal .am-header',
                    popover: {
                        title: 'Cabeçalho do Ativo',
                        description: 'Nome, tipo, criticidade, IP e estado do agente Wazuh ficam sempre visíveis no topo. O badge "Wazuh" ou "Manual" indica a origem do registo.',
                        side: 'bottom',
                        onPrevClick: () => {
                            closeAssetModal();
                            setTimeout(() => driverObj.movePrevious(), 300);
                        }
                    }
                },

                // ── Modal: Tab Visão Geral ───────────────────────────────
                {
                    element: '#assetModal .am-tabs',
                    popover: {
                        title: 'Separadores do Ativo',
                        description: 'O modal está dividido em três áreas: <b>Visão Geral</b> (dados e rede), <b>Risco & Tratamento</b> (riscos ligados) e <b>Análise de Postura IA</b>.',
                        side: 'bottom',
                        onNextClick: () => {
                            activateModalTab('overview');
                            driverObj.moveNext();
                        }
                    }
                },
                {
                    element: '#tab-overview .info-section:first-child',
                    popover: {
                        title: 'Informação Geral',
                        description: 'Responsável pelo ativo, quem o criou, origem (Wazuh ou manual) e data do último sync. Mantém a rastreabilidade do inventário.',
                        side: 'right'
                    }
                },
                {
                    element: '#mTagsWrap',
                    popover: {
                        title: 'Tags & Classificação',
                        description: 'As tags permitem agrupar ativos por ambiente (ex.: Produção, DMZ), framework (PCI-DSS) ou criticidade. Podes adicionar novas tags diretamente aqui.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#tab-overview .info-section:nth-child(3)',
                    popover: {
                        title: 'Informação de Rede',
                        description: 'IP, MAC, hostname e domínio do ativo — dados essenciais para correlacionar alertas do SIEM com o ativo correto.',
                        side: 'right'
                    }
                },
                {
                    element: '.risk-intrinsic-card',
                    popover: {
                        title: 'Risco Intrínseco',
                        description: 'Score calculado como <b>Probabilidade × Impacto</b>. A matriz 5×5 mostra visualmente onde este ativo se posiciona. Podes ajustar os valores e guardar.',
                        side: 'left'
                    }
                },

                // ── Modal: Tab Risco & Tratamento ────────────────────────
                {
                    element: '#assetModal [data-tab="risk"]',
                    popover: {
                        title: 'Risco & Tratamento',
                        description: 'Lista todos os riscos associados a este ativo e os planos de tratamento em curso. Podes criar um novo risco diretamente a partir daqui.',
                        side: 'bottom',
                        onNextClick: () => {
                            activateModalTab('risk');
                            driverObj.moveNext();
                        }
                    }
                },
                {
                    element: '#tab-risk',
                    popover: {
                        title: 'Riscos Associados',
                        description: 'Cada card mostra o título do risco, score, estado e planos de tratamento. O botão <b>"Criar Risco"</b> abre o módulo de riscos pré-preenchido com este ativo.',
                        side: 'top'
                    }
                },

                // ── Modal: Tab Análise IA ────────────────────────────────
                {
                    element: '#assetModal [data-tab="ai"]',
                    popover: {
                        title: 'Análise de Postura IA',
                        description: 'A IA analisa os dados deste ativo — configurações, OS, risco e histórico — e gera um relatório de postura de segurança com recomendações práticas.',
                        side: 'bottom',
                        onNextClick: () => {
                            activateModalTab('ai');
                            driverObj.moveNext();
                        }
                    }
                },
                {
                    element: '#tab-ai',
                    popover: {
                        title: 'Relatório de Postura IA',
                        description: 'Clica em <b>"Gerar nova análise"</b> para que o Gemini avalie este ativo. O histórico de análises anteriores fica guardado para comparação ao longo do tempo.',
                        side: 'top'
                    }
                },
                {
                    element: '#btnGenerateAiAnalysis',
                    popover: {
                        title: 'Gerar Análise',
                        description: 'Um clique e a IA processa o contexto completo do ativo — incluindo tipo, IP, criticidade e riscos — e devolve um relatório de conformidade detalhado em segundos.',
                        side: 'bottom'
                    }
                }
            ];

            driverObj = driver({
                showProgress: true,
                progressText: 'Passo {{current}} de {{total}}',
                nextBtnText: 'Próximo →',
                prevBtnText: '← Anterior',
                doneBtnText: 'Terminar',
                steps: steps,
                onDestroyStarted: () => {
                    closeAssetModal();
                    driverObj.destroy();
                }
            });

            driverObj.drive();
            return; // Sai cedo para não executar o bloco genérico abaixo
        }

        // 3. WALKTHROUGH DO COMPLIANCE
        else if (path.includes('compliance')) {
            steps = [
                {
                    element: '#page-compliance',
                    popover: {
                        title: 'Módulo de Compliance',
                        description: 'Aqui avalias a conformidade da tua organização face a frameworks como <b>QNRCS</b> e <b>NIS2</b>. Cada controlo pode ser marcado como Conforme, Parcial ou Não conforme.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#fwFilter',
                    popover: {
                        title: 'Filtro de Framework',
                        description: 'Seleciona entre QNRCS, NIS2 ou todos os frameworks para filtrar os controlos apresentados abaixo.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#statusFilter',
                    popover: {
                        title: 'Filtro de Estado',
                        description: 'Filtra os controlos por estado: <b>Conforme</b>, <b>Parcial</b> ou <b>Não conforme</b>. Útil para identificar rapidamente as lacunas.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiBar',
                    popover: {
                        title: 'Indicadores de Conformidade',
                        description: 'Resumo rápido do número de controlos conformes, parciais e não conformes para o framework selecionado.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#globalProgressWrap',
                    popover: {
                        title: 'Conformidade Global',
                        description: 'Barra de progresso que mostra a percentagem global de conformidade. O objetivo é aproximar este valor de 100%.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#complianceList',
                    popover: {
                        title: 'Lista de Controlos',
                        description: 'Os controlos estão agrupados por domínio (ex.: Gestão de Riscos, Controlo de Acessos). Clica em qualquer controlo para abrir o painel de avaliação.',
                        side: 'top'
                    }
                },
                {
                    element: '#btnExpandAll',
                    popover: {
                        title: 'Expandir / Colapsar',
                        description: 'Expande todos os grupos de uma vez para teres uma visão completa, ou colapsa para navegar mais facilmente entre domínios.',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 4. WALKTHROUGH DE DOCUMENTOS & EVIDÊNCIAS
        else if (path.includes('doc') || path.includes('evidencia') || path.includes('evidence')) {
            steps = [
                {
                    element: '#page-docs',
                    popover: {
                        title: 'Documentos & Evidências',
                        description: 'Repositório central de políticas, procedimentos, evidências e frameworks normativos. Tudo o que suporta a conformidade da tua organização fica aqui.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#frameworkPanel',
                    popover: {
                        title: 'Frameworks & Normas Oficiais',
                        description: 'Lista dos frameworks indexados (QNRCS, NIS2, ISO 27001). Estes documentos normativos são usados como contexto pela IA para gerar análises e sugestões de controlos.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#fwTbody',
                    popover: {
                        title: 'Tabela de Frameworks',
                        description: 'Clica em "Detalhes" num framework para visualizar o PDF e consultar as informações de origem e versão. Podes também atualizar a versão ou marcar como obsoleto.',
                        side: 'top'
                    }
                },
                {
                    element: '#docsTbody',
                    popover: {
                        title: 'Documentos no Sistema',
                        description: 'Políticas, procedimentos, relatórios e evidências carregadas. Cada documento tem estado (<b>Ativo / Pendente</b>), versão e associações a controlos de compliance.',
                        side: 'top'
                    }
                },
                {
                    element: '#docsCount',
                    popover: {
                        title: 'Total de Documentos',
                        description: 'Contador em tempo real do total de documentos registados no sistema.',
                        side: 'left'
                    }
                },
                {
                    element: '#btnOpenUploadDoc',
                    popover: {
                        title: 'Upload de Documento',
                        description: 'Carrega um novo ficheiro (PDF, DOCX, TXT) ou usa o <b>Assistente IA</b> para gerar automaticamente uma política ou procedimento alinhado com QNRCS/NIS2.',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 5. WALKTHROUGH DE TRATAMENTO
        else if (path.includes('treatment') || path.includes('tratamento')) {
            steps = [
                {
                    element: '#kpiTotal',
                    popover: {
                        title: 'Total de Planos',
                        description: 'Visão geral do número total de planos de tratamento de risco.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiOverdue',
                    popover: {
                        title: 'Planos em Atraso',
                        description: 'Planos que já ultrapassaram a data limite estipulada.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiDoing',
                    popover: {
                        title: 'Em Curso',
                        description: 'Planos que estão atualmente a ser executados.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiTodo',
                    popover: {
                        title: 'Por Iniciar',
                        description: 'Planos que ainda não começaram.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiDone',
                    popover: {
                        title: 'Concluídos',
                        description: 'Planos de tratamento que já foram finalizados.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#treatSearch',
                    popover: {
                        title: 'Pesquisa',
                        description: 'Pesquisa planos por nome, ativo associado ou responsável.',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 6. WALKTHROUGH DE INCIDENTES
        else if (path.includes('incidents') || path.includes('incidentes')) {
            steps = [
                {
                    element: '#btnNewIncident',
                    popover: {
                        title: 'Registar Incidente',
                        description: 'Clica aqui para registar um novo incidente de segurança e iniciar o processo de resposta.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiOpen',
                    popover: {
                        title: 'Incidentes Abertos',
                        description: 'Número de incidentes atualmente em aberto e que requerem atenção.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiContained',
                    popover: {
                        title: 'Incidentes Contidos',
                        description: 'Incidentes que já foram isolados, mas ainda não estão totalmente resolvidos.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#incFilterStatus',
                    popover: {
                        title: 'Filtro por Estado',
                        description: 'Filtra os incidentes na lista pelo seu estado atual (Aberto, Contido, Resolvido, etc).',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 7. WALKTHROUGH DE RISCOS
        else if (path.includes('risks') || path.includes('riscos')) {
            steps = [
                {
                    element: '#rkKpiTotal',
                    popover: {
                        title: 'Total de Riscos',
                        description: 'Número total de riscos registados no sistema.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#rkKpiCritical',
                    popover: {
                        title: 'Riscos Críticos',
                        description: 'Atenção aos riscos classificados como críticos. Estes devem ser a prioridade máxima.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#riskSearch',
                    popover: {
                        title: 'Pesquisa',
                        description: 'Pesquisa riscos por título ou descrição.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#riskLevelFilter',
                    popover: {
                        title: 'Filtro por Nível',
                        description: 'Filtra os riscos apresentados por nível de criticidade (Baixo, Médio, Alto, Crítico).',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 8. WALKTHROUGH DE AVALIAÇÕES
        else if (path.includes('assessments') || path.includes('avaliacoes')) {
            steps = [
                {
                    element: '#btnCompare',
                    popover: {
                        title: 'Comparar Avaliações',
                        description: 'Clica aqui para comparar diferentes avaliações e analisar a evolução da maturidade ao longo do tempo.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiMaturity',
                    popover: {
                        title: 'Maturidade Atual',
                        description: 'Percentagem global de maturidade baseada na avaliação mais recente.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kpiCovered',
                    popover: {
                        title: 'Controlos Cobertos',
                        description: 'Número de controlos que cumprem totalmente os requisitos.',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 9. WALKTHROUGH DE AUDITORIA
        else if (path.includes('audit') || path.includes('auditoria')) {
            steps = [
                {
                    element: '#mToday',
                    popover: {
                        title: 'Eventos de Hoje',
                        description: 'Total de eventos de auditoria registados no dia de hoje.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#mRisk',
                    popover: {
                        title: 'Eventos de Risco',
                        description: 'Número de eventos sinalizados com risco associado.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#auditSearch',
                    popover: {
                        title: 'Pesquisa',
                        description: 'Pesquisa nos logs de auditoria por utilizador, ação ou recurso.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#auditFilterAction',
                    popover: {
                        title: 'Filtro por Ação',
                        description: 'Filtra os logs de auditoria por tipo de ação (Criação, Atualização, Exclusão, etc).',
                        side: 'bottom'
                    }
                },
                {
                    element: '#auditExportPdf',
                    popover: {
                        title: 'Exportar PDF',
                        description: 'Gera um relatório em PDF com os logs de auditoria filtrados.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#auditTable',
                    popover: {
                        title: 'Tabela de Logs',
                        description: 'Lista detalhada de todos os eventos de auditoria registados no sistema.',
                        side: 'top'
                    }
                }
            ];
        }

        // 10. WALKTHROUGH DE PERMISSÕES (RBAC)
        else if (path.includes('rbac')) {
            steps = [
                {
                    element: '#kpiRoleName',
                    popover: {
                        title: 'Papel Ativo',
                        description: 'O papel (role) atualmente selecionado para gestão.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#panel-users',
                    popover: {
                        title: 'Gestão de Utilizadores',
                        description: 'Painel para gerir utilizadores, associar papéis e ativar/desativar contas.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#panel-roles',
                    popover: {
                        title: 'Gestão de Papéis',
                        description: 'Painel para criar e gerir os papéis (roles) disponíveis no sistema.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#panel-matrix',
                    popover: {
                        title: 'Matriz de Permissões',
                        description: 'Configuração detalhada de quais permissões cada papel tem acesso.',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 11. WALKTHROUGH DO CHAT DE IA
        else if (path.includes('chat')) {
            steps = [
                {
                    element: '#chatThread',
                    popover: {
                        title: 'Janela de Chat',
                        description: 'A tua conversa com o assistente de IA focado em GRC.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#chatInput',
                    popover: {
                        title: 'Escrever Mensagem',
                        description: 'Escreve aqui a tua pergunta sobre normativos, políticas ou procedimentos.',
                        side: 'top'
                    }
                },
                {
                    element: '#chatSend',
                    popover: {
                        title: 'Enviar',
                        description: 'Clica para enviar a mensagem para análise.',
                        side: 'top'
                    }
                },
                {
                    element: '#sourcesList',
                    popover: {
                        title: 'Fontes Consultadas',
                        description: 'Os documentos e secções de normativos que a IA utilizou para gerar a resposta aparecerão aqui.',
                        side: 'left'
                    }
                },
                {
                    element: '#auditBadge',
                    popover: {
                        title: 'Auditoria de Fontes',
                        description: 'Mostra o estado da pesquisa e o número total de fontes encontradas para a tua pergunta.',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 12. WALKTHROUGH DE RELATÓRIOS (CNCS)
        else if (path.includes('reports-cncs') || path.includes('cncs')) {
            steps = [
                {
                    element: '#tabBtnAnnual',
                    popover: {
                        title: 'Relatório Anual',
                        description: 'Abre o formulário para o relatório anual de conformidade e segurança.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#tabBtn24h',
                    popover: {
                        title: 'Notificação 24h',
                        description: 'Abre o formulário para a notificação de incidente em 24h.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#formAnnual',
                    popover: {
                        title: 'Formulário',
                        description: 'Preenche aqui os dados requeridos (entidade, ano, atividades, urgência).',
                        side: 'right'
                    }
                }
            ];
        }

        // 13. WALKTHROUGH DE QUESTIONÁRIO
        else if (path.includes('questionnaire') || path.includes('questionario')) {
            steps = [
                {
                    element: '#scorePercent',
                    popover: {
                        title: 'Score Atual',
                        description: 'A tua pontuação baseada nas respostas dadas até ao momento.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#progressBar',
                    popover: {
                        title: 'Progresso',
                        description: 'Acompanha o número de perguntas respondidas face ao total.',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kYes',
                    popover: {
                        title: 'Conforme',
                        description: 'Número de controlos marcados como "Sim" (totalmente implementados).',
                        side: 'bottom'
                    }
                },
                {
                    element: '#kPartial',
                    popover: {
                        title: 'Parcial',
                        description: 'Número de controlos marcados como "Parcialmente implementados".',
                        side: 'bottom'
                    }
                },
                {
                    element: '#btnFinishQ',
                    popover: {
                        title: 'Finalizar',
                        description: 'Clica aqui quando terminares de responder para submeteres o questionário.',
                        side: 'bottom'
                    }
                }
            ];
        }

        // 14. SE NÃO HOUVER WALKTHROUGH ESPECÍFICO PARA A PÁGINA
        else {
            steps = [
                {
                    popover: {
                        title: 'Ajuda',
                        description: 'Estás no módulo atual. Navega pela interface usando os botões habituais.',
                        side: 'bottom'
                    }
                }
            ];
        }

        // Executar o roteiro (para dashboard e páginas genéricas)
        const driverObj = driver({
            showProgress: true,
            progressText: 'Passo {{current}} de {{total}}',
            nextBtnText: 'Próximo →',
            prevBtnText: '← Anterior',
            doneBtnText: 'Terminar',
            steps: steps
        });

        driverObj.drive();
    });
});
