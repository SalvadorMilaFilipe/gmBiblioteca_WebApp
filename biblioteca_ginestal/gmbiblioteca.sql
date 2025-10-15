-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19-Set-2025 às 17:42
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gmbiblioteca`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `autor`
--

CREATE TABLE `autor` (
  `au_cod` int(11) NOT NULL,
  `au_nome` varchar(100) DEFAULT NULL,
  `au_pais` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `codigo_postal`
--

CREATE TABLE `codigo_postal` (
  `cod_postal` varchar(8) NOT NULL,
  `cod_localidade` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `editora`
--

CREATE TABLE `editora` (
  `ed_cod` int(11) NOT NULL,
  `ed_nome` varchar(28) NOT NULL,
  `ed_morada` varchar(40) NOT NULL,
  `ed_email` varchar(36) NOT NULL,
  `ed_codpostal` varchar(8) NOT NULL,
  `ed_tlm` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `genero`
--

CREATE TABLE `genero` (
  `ge_genero` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `idioma`
--

CREATE TABLE `idioma` (
  `id_idioma` varchar(16) NOT NULL,
  `id_pais` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `livros`
--

CREATE TABLE `livros` (
  `li_cod` int(11) NOT NULL,
  `li_isbn` int(11) NOT NULL,
  `li_titulo` varchar(40) NOT NULL,
  `li_editora` int(11) NOT NULL,
  `li_idioma` varchar(16) NOT NULL,
  `li_edicao` int(11) NOT NULL,
  `li_ano` int(11) NOT NULL,
  `li_autor` varchar(28) NOT NULL,
  `li_genero` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `livro_exemplar`
--

CREATE TABLE `livro_exemplar` (
  `ex_cod` int(11) NOT NULL,
  `ex_li_cod` int(11) DEFAULT NULL,
  `ex_estado` varchar(50) DEFAULT NULL,
  `ex_disponivel` tinyint(1) DEFAULT NULL,
  `ex_permrequisicao` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `requisicao`
--

CREATE TABLE `requisicao` (
  `re_cod` int(11) NOT NULL,
  `re_utcod` int(11) DEFAULT NULL,
  `re_lexcod` int(11) DEFAULT NULL,
  `re_datarequisicao` date DEFAULT NULL,
  `re_datadevolucao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `utente`
--

CREATE TABLE `utente` (
  `ut_cod` int(11) NOT NULL,
  `ut_nome` varchar(100) DEFAULT NULL,
  `ut_email` varchar(100) DEFAULT NULL,
  `ut_turma` varchar(50) DEFAULT NULL,
  `ut_ano` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `autor`
--
ALTER TABLE `autor`
  ADD PRIMARY KEY (`au_cod`);

--
-- Índices para tabela `editora`
--
ALTER TABLE `editora`
  ADD PRIMARY KEY (`ed_cod`);

--
-- Índices para tabela `genero`
--
ALTER TABLE `genero`
  ADD PRIMARY KEY (`ge_genero`);

--
-- Índices para tabela `idioma`
--
ALTER TABLE `idioma`
  ADD PRIMARY KEY (`id_idioma`);

--
-- Índices para tabela `livros`
--
ALTER TABLE `livros`
  ADD PRIMARY KEY (`li_cod`);

--
-- Índices para tabela `livro_exemplar`
--
ALTER TABLE `livro_exemplar`
  ADD PRIMARY KEY (`ex_cod`);

--
-- Índices para tabela `requisicao`
--
ALTER TABLE `requisicao`
  ADD PRIMARY KEY (`re_cod`),
  ADD KEY `re_utcod` (`re_utcod`),
  ADD KEY `re_lexcod` (`re_lexcod`);

--
-- Índices para tabela `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`ut_cod`);

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `requisicao`
--
ALTER TABLE `requisicao`
  ADD CONSTRAINT `requisicao_ibfk_1` FOREIGN KEY (`re_utcod`) REFERENCES `utente` (`ut_cod`),
  ADD CONSTRAINT `requisicao_ibfk_2` FOREIGN KEY (`re_lexcod`) REFERENCES `livro_exemplar` (`ex_cod`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
