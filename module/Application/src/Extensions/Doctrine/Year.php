<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * YearFunction ::= "YEAR" "(" ArithmeticPrimary ")"
 */
class Year extends FunctionNode
{
    public Node $yearExpression;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->yearExpression = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'EXTRACT(YEAR FROM ' . $this->yearExpression->dispatch($sqlWalker) . ')';
    }
}
