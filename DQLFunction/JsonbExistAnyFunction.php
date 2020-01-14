<?php

namespace Repregid\ApiBundle\DQLFunction;


use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class JsonbExistAnyFunction extends FunctionNode
{
    protected $operator;
    protected $first;
    protected $second;

    public const name = "jsonb_exists_any";

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf("jsonb_exists_any(%s, %s)",
            $this->first->dispatch($sqlWalker),
            $this->second->dispatch($sqlWalker)
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
        $parser->match(Lexer::T_COMMA);
        $this->second   = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}