<?php

namespace Repregid\ApiBundle\DQLFunction;


use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class JsonExtractPathTextFunction extends FunctionNode
{
    protected $operator;
    protected $first;
    protected $second;

    public const name = "json_extract_path_text";

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $primary = $this->first->dispatch($sqlWalker);
        foreach ($this->second as $item) {
            $args[] = $item->dispatch($sqlWalker);
        }
        return sprintf("json_extract_path_text(%s, %s)",
            $primary,
            implode(',', $args)
        );
    }

    /**
     * @param Parser $parser
     *
     * @return void
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->first    = $parser->ArithmeticPrimary();
        $continueParsing = !$parser->getLexer()->isNextToken(Lexer::T_CLOSE_PARENTHESIS);
        while ($continueParsing) {
            $parser->match(Lexer::T_COMMA);
            $this->second[]   = $parser->StringPrimary();
            $continueParsing = $parser->getLexer()->isNextToken(Lexer::T_COMMA);
        }
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}