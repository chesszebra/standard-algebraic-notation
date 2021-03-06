<?php declare(strict_types=1);
/**
 * standard-algebraic-notation (https://github.com/chesszebra/standard-algebraic-notation)
 *
 * @link https://github.com/chesszebra/standard-algebraic-notation for the canonical source repository
 * @copyright Copyright (c) 2017 Chess Zebra (https://chesszebra.com)
 * @license https://github.com/chesszebra/standard-algebraic-notation/blob/master/LICENSE.md MIT
 */

namespace ChessZebra\StandardAlgebraicNotation;

use ChessZebra\StandardAlgebraicNotation\Exception\InvalidArgumentException;
use ChessZebra\StandardAlgebraicNotation\Exception\RuntimeException;

/**
 * The representation of a Standard Algebraic Notation (SAN) notation.
 */
final class Notation
{
    public const PIECE_PAWN = null;
    public const PIECE_BISHOP = 'B';
    public const PIECE_KING = 'K';
    public const PIECE_KNIGHT = 'N';
    public const PIECE_QUEEN = 'Q';
    public const PIECE_ROOK = 'R';

    public const CASTLING_KING_SIDE = 'O-O';
    public const CASTLING_QUEEN_SIDE = 'O-O-O';

    public const ANNOTATION_BLUNDER = '??';
    public const ANNOTATION_MISTAKE = '?';
    public const ANNOTATION_INTERESTING_MOVE = '?!';
    public const ANNOTATION_GOOD_MOVE = '!';
    public const ANNOTATION_BRILLIANT_MOVE = '!!';

    /**
     * The original value.
     *
     * @var string
     */
    private $value;

    /**
     * The type of castling move that was made.
     *
     * @var null|string
     */
    private $castling;

    /**
     * The target column.
     *
     * @var string
     */
    private $targetColumn;

    /**
     * The target row.
     *
     * @var int
     */
    private $targetRow;

    /**
     * The piece that was moved.
     *
     * @var string
     */
    private $movedPiece;

    /**
     * The column from where the move was made.
     *
     * @var string
     */
    private $movedPieceDisambiguationColumn;

    /**
     * The row from where the move was made.
     *
     * @var int
     */
    private $movedPieceDisambiguationRow;

    /**
     * The piece to which the pawn was promoted into.
     *
     * @var string
     */
    private $promotedPiece;

    /**
     * A flag that indicates whether or not a piece was captured.
     *
     * @var bool
     */
    private $capture;

    /**
     * A flag that indicates that a check move was made.
     *
     * @var bool
     */
    private $check;

    /**
     * A flag that indicates that a checkmate move was made.
     *
     * @var bool
     */
    private $checkmate;

    /**
     * An annotation given to a move.
     * For example "Nbd7?!"
     *
     * @var null|string
     */
    private $annotation;

    /**
     * A flag that indicates whether or not this notation is a long-SAN.
     *
     * @var bool
     */
    private $longSan;

    /**
     * Initializes a new instance of this class.
     *
     * @param string $value
     * @throws InvalidArgumentException Thrown when an invalid value is provided.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
        $this->capture = false;
        $this->check = false;
        $this->checkmate = false;
        $this->longSan = false;

        $this->parse($value);
    }

    /**
     * Parses a SAN value.
     *
     * @param string $value The value to parse.
     * @throws InvalidArgumentException Thrown when an invalid value is provided.
     */
    private function parse(string $value): void
    {
        // Check for castling:
        if (preg_match('/^(O-O|O-O-O)(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->castling = $matches[1];
            $this->check = $matches[2] === '+';
            $this->checkmate = $matches[2] === '#';
            $this->annotation = isset($matches[3]) ? $matches[3] : null;
            return;
        }

        // Check for castling:
        if (preg_match('/^(0-0|0-0-0)(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->castling = $matches[1];
            $this->check = $matches[2] === '+';
            $this->checkmate = $matches[2] === '#';
            $this->annotation = isset($matches[3]) ? $matches[3] : null;
            return;
        }

        // Pawn movement:
        if (preg_match('/^([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->targetColumn = $matches[1];
            $this->targetRow = (int)$matches[2];
            $this->check = $matches[3] === '+';
            $this->checkmate = $matches[3] === '#';
            $this->movedPiece = self::PIECE_PAWN;
            $this->annotation = isset($matches[4]) ? $matches[4] : null;
            return;
        }

        // Pawn movement (long san):
        if (preg_match('/^([a-h])([1-8])([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPieceDisambiguationColumn = $matches[1];
            $this->movedPieceDisambiguationRow = (int)$matches[2];
            $this->targetColumn = $matches[3];
            $this->targetRow = (int)$matches[4];
            $this->check = $matches[5] === '+';
            $this->checkmate = $matches[5] === '#';
            $this->movedPiece = self::PIECE_PAWN;
            $this->annotation = isset($matches[6]) ? $matches[6] : null;
            $this->longSan = true;
            return;
        }

        // Piece movement:
        if (preg_match('/^([KQBNR])([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = $matches[1];
            $this->targetColumn = $matches[2];
            $this->targetRow = (int)$matches[3];
            $this->check = $matches[4] === '+';
            $this->checkmate = $matches[4] === '#';
            $this->annotation = isset($matches[5]) ? $matches[5] : null;
            return;
        }

        // Piece movement from a specific column:
        if (preg_match('/^([KQBNR])([a-h])([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = $matches[1];
            $this->movedPieceDisambiguationColumn = $matches[2];
            $this->targetColumn = $matches[3];
            $this->targetRow = (int)$matches[4];
            $this->check = $matches[5] === '+';
            $this->checkmate = $matches[5] === '#';
            $this->annotation = isset($matches[6]) ? $matches[6] : null;
            return;
        }

        // Piece movement from a specific row:
        if (preg_match('/^([KQBNR])([0-9])([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = $matches[1];
            $this->movedPieceDisambiguationRow = (int)$matches[2];
            $this->targetColumn = $matches[3];
            $this->targetRow = (int)$matches[4];
            $this->check = $matches[5] === '+';
            $this->checkmate = $matches[5] === '#';
            $this->annotation = isset($matches[6]) ? $matches[6] : null;
            return;
        }

        // Piece movement from a specific column and row (long san):
        if (preg_match('/^([KQBNR])([a-h])([0-9])([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = $matches[1];
            $this->movedPieceDisambiguationColumn = $matches[2];
            $this->movedPieceDisambiguationRow = (int)$matches[3];
            $this->targetColumn = $matches[4];
            $this->targetRow = (int)$matches[5];
            $this->check = $matches[6] === '+';
            $this->checkmate = $matches[6] === '#';
            $this->annotation = isset($matches[7]) ? $matches[7] : null;
            $this->longSan = true;
            return;
        }

        // Pawn capture:
        if (preg_match('/^([a-h])x([a-h])([1-8])(?:=?([KQBNR]))?(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->targetColumn = $matches[2];
            $this->targetRow = (int)$matches[3];
            $this->movedPiece = self::PIECE_PAWN;
            $this->movedPieceDisambiguationColumn = $matches[1];
            $this->capture = true;
            $this->promotedPiece = $matches[4] ?: null;
            $this->check = $matches[5] === '+';
            $this->checkmate = $matches[5] === '#';
            $this->annotation = isset($matches[6]) ? $matches[6] : null;
            return;
        }

        // Pawn capture (long san):
        if (preg_match('/^([a-h])([1-8])x([a-h])([1-8])(?:=?([KQBNR]))?(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->targetColumn = $matches[3];
            $this->targetRow = (int)$matches[4];
            $this->movedPiece = self::PIECE_PAWN;
            $this->movedPieceDisambiguationColumn = $matches[1];
            $this->movedPieceDisambiguationRow = (int)$matches[2];
            $this->capture = true;
            $this->promotedPiece = $matches[5] ?: null;
            $this->check = $matches[6] === '+';
            $this->checkmate = $matches[6] === '#';
            $this->annotation = isset($matches[7]) ? $matches[7] : null;
            $this->longSan = true;
            return;
        }

        // Piece capture:
        if (preg_match('/^([KQBNR])x([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = $matches[1];
            $this->targetColumn = $matches[2];
            $this->targetRow = (int)$matches[3];
            $this->check = $matches[4] === '+';
            $this->checkmate = $matches[4] === '#';
            $this->capture = true;
            $this->annotation = isset($matches[5]) ? $matches[5] : null;
            return;
        }

        // Piece capture from a specific column:
        if (preg_match('/^([KQBNR])([a-h])x([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = $matches[1];
            $this->movedPieceDisambiguationColumn = $matches[2];
            $this->targetColumn = $matches[3];
            $this->targetRow = (int)$matches[4];
            $this->check = $matches[5] === '+';
            $this->checkmate = $matches[5] === '#';
            $this->capture = true;
            $this->annotation = isset($matches[6]) ? $matches[6] : null;
            return;
        }

        // Piece capture from a specific row:
        if (preg_match('/^([KQBNR])([0-9])x([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = $matches[1];
            $this->movedPieceDisambiguationRow = (int)$matches[2];
            $this->targetColumn = $matches[3];
            $this->targetRow = (int)$matches[4];
            $this->check = $matches[5] === '+';
            $this->checkmate = $matches[5] === '#';
            $this->capture = true;
            $this->annotation = isset($matches[6]) ? $matches[6] : null;
            return;
        }

        // Piece capture from a specific column and row (long san):
        if (preg_match('/^([KQBNR])([a-h])([0-9])x([a-h])([1-8])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = $matches[1];
            $this->movedPieceDisambiguationColumn = $matches[2];
            $this->movedPieceDisambiguationRow = (int)$matches[3];
            $this->targetColumn = $matches[4];
            $this->targetRow = (int)$matches[5];
            $this->check = $matches[6] === '+';
            $this->checkmate = $matches[6] === '#';
            $this->capture = true;
            $this->annotation = isset($matches[7]) ? $matches[7] : null;
            $this->longSan = true;
            return;
        }

        // Check for pawn promotion:
        if (preg_match('/^([a-h])([1-8])=?([KQBNR])(\+|\#?)(\?\?|\?|\?\!|\!|\!\!)?$/', $value, $matches)) {
            $this->movedPiece = self::PIECE_PAWN;
            $this->targetColumn = $matches[1];
            $this->targetRow = (int)$matches[2];
            $this->promotedPiece = $matches[3];
            $this->check = $matches[4] === '+';
            $this->checkmate = $matches[4] === '#';
            $this->annotation = isset($matches[5]) ? $matches[5] : null;
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'The value "%s" could not be parsed.',
            $value
        ));
    }

    /**
     * Checks if this move is a castling move.
     *
     * @return bool Returns true when this is a castling move; false otherwise.
     */
    public function isCastlingMove(): bool
    {
        return $this->isCastlingTowardsKingSide() || $this->isCastlingTowardsQueenSide();
    }

    /**
     * Checks if this is a castling move towards the king side.
     *
     * @return bool Returns true if this is a castling move; false otherwise.
     */
    public function isCastlingTowardsKingSide(): bool
    {
        return $this->castling === self::CASTLING_KING_SIDE;
    }

    /**
     * Checks if this is a castling move towards the queen side.
     *
     * @return bool Returns true if this is a castling move; false otherwise.
     */
    public function isCastlingTowardsQueenSide(): bool
    {
        return $this->castling === self::CASTLING_QUEEN_SIDE;
    }

    /**
     * Gets the original value.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Gets the castling value.
     *
     * @return null|string
     */
    public function getCastling(): ?string
    {
        return $this->castling;
    }

    /**
     * Gets the column to where the move was made.
     *
     * @return null|string
     */
    public function getTargetColumn(): ?string
    {
        return $this->targetColumn;
    }

    /**
     * Gets the column index to where the move was made.
     *
     * @return int|null
     */
    public function getTargetColumnIndex(): ?int
    {
        if ($this->targetColumn === null) {
            return null;
        }

        return ord($this->targetColumn) - 97;
    }

    /**
     * Duplicates the notation with a column based on an index from 0 to 7.
     *
     * @param int $index The index of the column to use.
     * @return Notation
     * @throws InvalidArgumentException Thrown when an invalid SAN value is created.
     * @throws RuntimeException Thrown when no target row is set.
     */
    public function withTargetColumnIndex(int $index): Notation
    {
        if (!$this->getTargetRow()) {
            throw new RuntimeException('No row has been set.');
        }

        $notation = chr(97 + $index) . $this->getTargetRow();

        return new self($notation);
    }

    /**
     * Gets the row number to where the move was made.
     *
     * @return int|null
     */
    public function getTargetRow(): ?int
    {
        return $this->targetRow;
    }

    /**
     * Duplicates the notation with a new row.
     *
     * @param int $row A value between 1 and 8.
     * @return Notation
     * @throws InvalidArgumentException Thrown when an invalid SAN value is created.
     */
    public function withTargetRow(int $row): Notation
    {
        $notation = $this->getTargetColumn() . $row;

        return new self($notation);
    }

    /**
     * Gets the target notation.
     *
     * @return string
     */
    public function getTargetNotation(): string
    {
        return $this->getTargetColumn() . $this->getTargetRow();
    }

    /**
     * Gets the piece that was moved.
     *
     * @return null|string Returns the piece or null when a pawn was moved.
     */
    public function getMovedPiece(): ?string
    {
        return $this->movedPiece;
    }

    /**
     * Gets the disambiguation column.
     *
     * @return null|string
     */
    public function getMovedPieceDisambiguationColumn(): ?string
    {
        return $this->movedPieceDisambiguationColumn;
    }

    /**
     * Sets the disambiguation column.
     *
     * @param null|string $movedPieceDisambiguationColumn
     */
    public function setMovedPieceDisambiguationColumn(?string $movedPieceDisambiguationColumn): void
    {
        $this->movedPieceDisambiguationColumn = $movedPieceDisambiguationColumn;
    }

    /**
     * Gets the disambiguation row.
     *
     * @return int|null
     */
    public function getMovedPieceDisambiguationRow(): ?int
    {
        return $this->movedPieceDisambiguationRow;
    }

    /**
     * Sets the disambiguation row.
     *
     * @param int|null $movedPieceDisambiguationRow
     */
    public function setMovedPieceDisambiguationRow(?int $movedPieceDisambiguationRow): void
    {
        $this->movedPieceDisambiguationRow = $movedPieceDisambiguationRow;
    }

    /**
     * Gets the flag that indicates if this was a capture move.
     *
     * @return bool
     */
    public function isCapture(): bool
    {
        return $this->capture;
    }

    /**
     * Gets the piece into which the pawn was promoted.
     *
     * @return null|string
     */
    public function getPromotedPiece(): ?string
    {
        return $this->promotedPiece;
    }

    /**
     * Gets the flag that indicates whether or not the move returned into a check state.
     *
     * @return bool
     */
    public function isCheck(): bool
    {
        return $this->check;
    }

    /**
     * Gets the flag that indicates whether or not the move returned into a checkmate state.
     *
     * @return bool
     */
    public function isCheckmate(): bool
    {
        return $this->checkmate;
    }

    /**
     * Gets the annotation that has been given to this move.
     *
     * @return null|string
     */
    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }

    /**
     * Checks whether or not this notation is in long-SAN format.
     *
     * @return bool
     */
    public function isLongSan(): bool
    {
        return $this->longSan;
    }

    /**
     * Converts the move to a string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->getValue();
    }

    /**
     * Converts the move to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
